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
        $uuid = $request->input('user');
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        $user->createOrGetStripeCustomer();
        $setupIntent = $user->createSetupIntent();
        return response()->json([
            'clientSecret' => $setupIntent->client_secret,
        ]);
    }
}