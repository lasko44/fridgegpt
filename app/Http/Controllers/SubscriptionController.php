<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function store(Request $request): \Illuminate\Http\JsonResponse
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

        return Inertia::location(route('home'))
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
    public function destroy(string $id)
    {
        //
    }
}
