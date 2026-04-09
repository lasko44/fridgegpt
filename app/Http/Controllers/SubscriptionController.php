<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subscription\CancelSubscriptionRequest;
use App\Http\Requests\Subscription\CreateSubscriptionRequest;
use App\Models\User;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for handling subscription-related web requests.
 */
class SubscriptionController extends Controller
{
    /**
     * Show the subscription creation form.
     */
    public function create(): Response
    {
        return Inertia::render('Subscription');
    }

    /**
     * Store a newly created subscription.
     */
    public function store(CreateSubscriptionRequest $request, SubscriptionService $subscriptionService): RedirectResponse
    {
        try {
            $subscriptionService->createSubscription(
                $request->user(),
                $request->getPaymentMethod()
            );

            return redirect()->route('home')
                ->with('success', 'Subscription created successfully!');
        } catch (Exception $e) {
            Log::error('Subscription creation failed: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create subscription. Please try again.']);
        }
    }

    /**
     * Cancel the user's subscription.
     */
    public function destroy(CancelSubscriptionRequest $request, SubscriptionService $subscriptionService): RedirectResponse
    {
        try {
            $subscriptionService->cancelSubscription($request->user());

            return redirect()->back()
                ->with('success', 'Subscription cancelled successfully!');
        } catch (Exception $e) {
            Log::error('Subscription cancellation failed: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to cancel subscription.']);
        }
    }
}
