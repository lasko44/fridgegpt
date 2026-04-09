<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

/**
 * Service for handling subscription-related business logic.
 */
class SubscriptionService
{
    private const STRIPE_PRICE_ID = 'price_1Rmy2JPDvw13epACAiqCdSJU';
    private const TRIAL_DAYS = 7;

    private ?User $user = null;
    private ?Subscription $subscription = null;

    /**
     * Set the user context for legacy methods.
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->subscription = $user->subscription('default');
        return $this;
    }

    /**
     * Create a new subscription for a user.
     *
     * @throws Exception
     */
    public function createSubscription(User $user, string $paymentMethod): Subscription
    {
        try {
            $user->createOrGetStripeCustomer();
            $user->addPaymentMethod($paymentMethod);
            $user->updateDefaultPaymentMethod($paymentMethod);

            $subscription = $user->newSubscription('default', self::STRIPE_PRICE_ID)
                ->trialDays(self::TRIAL_DAYS)
                ->create($paymentMethod);

            $user->subscribe();

            return $subscription;
        } catch (Exception $e) {
            Log::error('Subscription creation failed for user: ' . $user->uuid . ' Error: ' . $e->getMessage());
            throw new Exception('Failed to create subscription: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a user's subscription.
     *
     * @throws Exception
     */
    public function cancelSubscription(User $user): bool
    {
        try {
            $this->setUser($user);
            $this->subscription?->cancel();
            $this->markForRemoval();
            $user->save();
            return true;
        } catch (Exception $e) {
            Log::error('Subscription cancellation failed for user: ' . $user->uuid . ' Error: ' . $e->getMessage());
            throw new Exception('Failed to cancel subscription');
        }
    }

    /**
     * @deprecated Use cancelSubscription() instead
     * @throws Exception
     */
    public function cancel(): bool
    {
        if (!$this->user) {
            throw new Exception('User not set. Call setUser() first.');
        }

        return $this->cancelSubscription($this->user);
    }

    /**
     * Get the subscription start date.
     */
    public function startedAtDate(): ?Carbon
    {
        return $this->subscription ? Carbon::parse($this->subscription->created_at) : null;
    }

    /**
     * Check if the subscription is in trial period.
     */
    public function isTrialing(): bool
    {
        return $this->subscription?->onTrial() ?? false;
    }

    /**
     * Get the trial end date.
     */
    public function trialEndsAtDate(): ?Carbon
    {
        $trialEndsAt = $this->subscription?->trial_ends_at;
        return $trialEndsAt ? Carbon::parse($trialEndsAt) : null;
    }

    /**
     * Mark the user for subscription removal at the appropriate date.
     */
    public function markForRemoval(): void
    {
        if (!$this->user) {
            return;
        }

        if ($this->isTrialing()) {
            $this->user->remove_subscribed_on = $this->trialEndsAtDate();
        } else {
            $this->user->remove_subscribed_on = $this->user->currentBillPeriodEnd();
        }
    }

    /**
     * Get subscription details for a user.
     *
     * @return array<string, mixed>
     */
    public function getSubscriptionDetails(User $user): array
    {
        $this->setUser($user);

        return [
            'is_subscribed' => $user->is_subscribed,
            'is_trialing' => $this->isTrialing(),
            'trial_ends_at' => $this->trialEndsAtDate(),
            'started_at' => $this->startedAtDate(),
            'current_period_end' => $user->currentBillPeriodEnd(),
        ];
    }
}