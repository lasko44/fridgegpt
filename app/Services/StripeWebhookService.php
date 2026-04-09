<?php

namespace App\Services;

use App\Models\TokenPackage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripeWebhookService
{
    public function __construct(private TokenService $tokenService) {}

    /**
     * Process a completed Stripe Checkout session for token purchase.
     */
    public function processTokenPurchase(array $session): bool
    {
        try {
            $metadata = $session['metadata'] ?? [];

            if (($metadata['type'] ?? '') !== 'token_purchase') {
                return false;
            }

            $userId = $metadata['user_id'] ?? null;
            $packageId = $metadata['package_id'] ?? null;

            if (!$userId || !$packageId) {
                Log::warning('Token purchase webhook missing metadata', ['session_id' => $session['id'] ?? 'unknown']);
                return false;
            }

            $user = User::find($userId);
            $package = TokenPackage::find($packageId);

            if (!$user || !$package) {
                Log::warning('Token purchase webhook: user or package not found', [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                ]);
                return false;
            }

            if (($session['payment_status'] ?? '') !== 'paid') {
                Log::warning('Token purchase webhook: payment not completed', [
                    'session_id' => $session['id'] ?? 'unknown',
                    'status' => $session['payment_status'] ?? 'unknown',
                ]);
                return false;
            }

            $sessionId = $session['id'] ?? 'unknown';

            // Prevent duplicate credits by checking if this session was already processed
            $existing = $user->tokenTransactions()
                ->where('metadata->stripe_session_id', $sessionId)
                ->exists();

            if ($existing) {
                Log::info('Token purchase webhook: already processed', ['session_id' => $sessionId]);
                return true;
            }

            $this->tokenService->creditPurchase($user, $package, $sessionId);

            Log::info('Token purchase processed via webhook', [
                'user_id' => $user->id,
                'package' => $package->name,
                'tokens' => $package->tokens,
                'session_id' => $sessionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Token purchase webhook failed', [
                'error' => $e->getMessage(),
                'session_id' => $session['id'] ?? 'unknown',
            ]);
            return false;
        }
    }
}
