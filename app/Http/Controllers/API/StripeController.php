<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $amount = $request->input('amount', 1000);

        if (!$user->hasStripeId()) {
            $user->createAsStripeCustomer();
        }

        $paymentIntent = $user->createSetupIntent();

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
        ]);
    }
}
