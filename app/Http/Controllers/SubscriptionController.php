<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\PaymentMethod;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {

        return Inertia::render('Subscription');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $paymentMethod = $request->input('payment_method');

        $user->createOrGetStripeCustomer();

        // Attach the payment method to the customer
        $user->addPaymentMethod($paymentMethod);

        // Set as default
        $user->updateDefaultPaymentMethod($paymentMethod);

        // Create subscription
        $user->newSubscription('default', 'price_1Rmy2JPDvw13epACAiqCdSJU')
            ->trialDays(7)
            ->create($paymentMethod);

        $user->subscribe();

        return redirect()->route('home')
            ->with('success', 'Subscription created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $userId): RedirectResponse
    {
        try{
            $user = auth()->user() ?? User::query()->findOrFail($userId);

            $subscriptionService = new SubscriptionService($user);
            $subscriptionService->cancel();

            return redirect()->back()
                ->with('flash.success', 'Subscription cancelled successfully!');
        }
        catch (Exception $exception){
            Log::error($exception->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to cancel subscription: ']);
        }

    }
}
