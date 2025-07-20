<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = User::find($request->input('user'));
        $user->createOrGetStripeCustomer();
        $setupIntent = $user->createSetupIntent();
        return response()->json([
            'clientSecret' => $setupIntent->client_secret,
        ]);
    }
}