<?php

namespace App\Policies;

use App\Models\User;

/**
 * Policy for authorizing subscription-related actions.
 */
class SubscriptionPolicy
{
    /**
     * Determine whether the user can view their subscription.
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create a subscription.
     */
    public function create(User $user): bool
    {
        return !$user->is_subscribed;
    }

    /**
     * Determine whether the user can cancel their subscription.
     */
    public function cancel(User $user): bool
    {
        return $user->is_subscribed;
    }

    /**
     * Determine whether the user can update their payment method.
     */
    public function updatePaymentMethod(User $user): bool
    {
        return $user->is_subscribed;
    }
}
