<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;

class SubscriptionService
{
    private User $user;
    private Subscription $subscription;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->subscription = $user->subscription('default');
    }

    /**
     * @throws Exception
     */
    public function cancel(): bool
    {
        try{
            $this->subscription?->cancel();
            $this->markForRemoval();
            return true;
        }
        catch (Exception $e) {
            Log::error('Error Canceling Subscription for User Id: '. $this->user->uuid. 'Error: '. $e->getMessage());
            throw new Exception('Subscription Cancellation Failure');
        }

    }

    public function startedAtDate(): Carbon
    {
        return Carbon::parse($this->subscription?->created_at);
    }

    public function isTrialing(): bool
    {
        return $this->subscription?->onTrial() ?? false;
    }

    public function trialEndsAtDate(): ?Carbon
    {
        $trialEndsAt = $this->subscription?->trial_ends_at;
        return $trialEndsAt ? Carbon::parse($trialEndsAt) : null;
    }

    public function markForRemoval(): void
    {
        if($this->isTrialing()){
            $this->user->remove_subscribed_on = $this->trialEndsAtDate();
        } else {
            $this->user->remove_subscribed_on = $this->user->currentBillPeriodEnd();
        }
    }
}