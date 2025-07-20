<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
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
    public function create()
    {
        return Inertia::render('Subscription');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $paymentMethod = $request->input('payment_method');

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        // Attach & set default
        $user->updateDefaultPaymentMethod($paymentMethod);

        // Create subscription
        $user->newSubscription('default', 'prod_SiOpc3dzcfg9ly') // Replace with your real Stripe Price ID
        ->create($paymentMethod);

        return response()->json(['message' => 'Subscription created successfully']);
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
