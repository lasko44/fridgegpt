<?php

namespace App\Services;

use App\Models\TokenCode;
use App\Models\TokenCodeRedemption;
use App\Models\TokenPackage;
use App\Models\TokenTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TokenService
{
    /**
     * Get the token cost for a feature.
     */
    public function getCost(string $feature): int
    {
        return (int) config("tokens.costs.{$feature}", 0);
    }

    /**
     * Get the human-readable label for a feature.
     */
    public function getLabel(string $feature): string
    {
        return config("tokens.labels.{$feature}", ucfirst($feature));
    }

    /**
     * Check if user has enough tokens for a feature.
     */
    public function hasTokensFor(User $user, string $feature): bool
    {
        return $user->token_balance >= $this->getCost($feature);
    }

    /**
     * Get user's current balance.
     */
    public function getBalance(User $user): int
    {
        return $user->token_balance;
    }

    /**
     * Spend tokens for a feature. Uses pessimistic locking.
     *
     * @throws Exception
     */
    public function spend(User $user, string $feature, array $metadata = []): TokenTransaction
    {
        $cost = $this->getCost($feature);
        if ($cost <= 0) {
            throw new Exception("No cost defined for feature: {$feature}");
        }

        return DB::transaction(function () use ($user, $feature, $cost, $metadata) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            if ($lockedUser->token_balance < $cost) {
                throw new Exception('Insufficient tokens. You need ' . $cost . ' token(s) but have ' . $lockedUser->token_balance . '.');
            }

            $newBalance = $lockedUser->token_balance - $cost;
            $lockedUser->update(['token_balance' => $newBalance]);

            // Update the original user instance
            $user->token_balance = $newBalance;

            $transaction = TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_SPEND,
                'amount' => -$cost,
                'balance_after' => $newBalance,
                'description' => $this->getLabel($feature),
                'metadata' => array_merge($metadata, ['feature' => $feature]),
            ]);

            Log::info("Token spent", [
                'user_id' => $lockedUser->id,
                'feature' => $feature,
                'cost' => $cost,
                'balance' => $newBalance,
            ]);

            return $transaction;
        });
    }

    /**
     * Credit tokens from a purchase.
     */
    public function creditPurchase(User $user, TokenPackage $package, string $stripeSessionId): TokenTransaction
    {
        return DB::transaction(function () use ($user, $package, $stripeSessionId) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            $newBalance = $lockedUser->token_balance + $package->tokens;
            $lockedUser->update(['token_balance' => $newBalance]);

            $user->token_balance = $newBalance;

            $transaction = TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_PURCHASE,
                'amount' => $package->tokens,
                'balance_after' => $newBalance,
                'description' => "Purchased {$package->name}",
                'metadata' => [
                    'package_id' => $package->id,
                    'stripe_session_id' => $stripeSessionId,
                    'price_cents' => $package->price_cents,
                ],
            ]);

            Log::info("Tokens purchased", [
                'user_id' => $lockedUser->id,
                'package' => $package->name,
                'tokens' => $package->tokens,
                'balance' => $newBalance,
            ]);

            return $transaction;
        });
    }

    /**
     * Credit bonus tokens (promo, admin grant, etc).
     */
    public function creditBonus(User $user, int $amount, string $reason): TokenTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            $newBalance = $lockedUser->token_balance + $amount;
            $lockedUser->update(['token_balance' => $newBalance]);

            $user->token_balance = $newBalance;

            return TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_BONUS,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason,
            ]);
        });
    }

    /**
     * Refund tokens.
     */
    public function refund(User $user, int $amount, string $reason): TokenTransaction
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            $newBalance = $lockedUser->token_balance + $amount;
            $lockedUser->update(['token_balance' => $newBalance]);

            $user->token_balance = $newBalance;

            return TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_REFUND,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => $reason,
            ]);
        });
    }

    /**
     * Redeem a promo/bonus code with rate limiting.
     *
     * @throws Exception
     */
    public function redeemCode(User $user, string $codeString): array
    {
        // Rate limit: 5 attempts per user per 15 minutes
        $cacheKey = "token_redeem_attempts:{$user->id}";
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 5) {
            throw new Exception('Too many redemption attempts. Please try again later.');
        }

        Cache::put($cacheKey, $attempts + 1, now()->addMinutes(15));

        $code = TokenCode::where('code', strtoupper(trim($codeString)))->first();

        if (!$code) {
            throw new Exception('Invalid code.');
        }

        $check = $code->canBeRedeemedBy($user);
        if (!$check['valid']) {
            throw new Exception($check['reason']);
        }

        return DB::transaction(function () use ($user, $code) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            $newBalance = $lockedUser->token_balance + $code->tokens;
            $lockedUser->update(['token_balance' => $newBalance]);

            $user->token_balance = $newBalance;

            $transaction = TokenTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => TokenTransaction::TYPE_BONUS,
                'amount' => $code->tokens,
                'balance_after' => $newBalance,
                'description' => "Redeemed code: {$code->code}",
                'metadata' => ['code_id' => $code->id, 'code' => $code->code],
            ]);

            TokenCodeRedemption::create([
                'token_code_id' => $code->id,
                'user_id' => $lockedUser->id,
                'tokens_received' => $code->tokens,
            ]);

            $code->increment('uses_count');

            // Auto-deactivate single-use codes
            if ($code->type === TokenCode::TYPE_SINGLE) {
                $code->update(['is_active' => false]);
            }

            Log::info("Code redeemed", [
                'user_id' => $lockedUser->id,
                'code' => $code->code,
                'tokens' => $code->tokens,
            ]);

            return [
                'success' => true,
                'tokens' => $code->tokens,
                'new_balance' => $newBalance,
                'message' => "{$code->tokens} tokens added to your account!",
            ];
        });
    }

    /**
     * Get transaction history.
     */
    public function getHistory(User $user, int $limit = 50): Collection
    {
        return $user->tokenTransactions()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get usage stats.
     */
    public function getUsageStats(User $user): array
    {
        return [
            'current_balance' => $user->token_balance,
            'spent_this_month' => $user->tokenTransactions()
                ->where('type', TokenTransaction::TYPE_SPEND)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum(DB::raw('ABS(amount)')),
            'total_spent' => $user->tokenTransactions()
                ->where('type', TokenTransaction::TYPE_SPEND)
                ->sum(DB::raw('ABS(amount)')),
            'total_purchased' => $user->tokenTransactions()
                ->where('type', TokenTransaction::TYPE_PURCHASE)
                ->sum('amount'),
        ];
    }

    /**
     * Get active token packages.
     */
    public function getPackages(): Collection
    {
        return TokenPackage::active()->ordered()->get();
    }
}
