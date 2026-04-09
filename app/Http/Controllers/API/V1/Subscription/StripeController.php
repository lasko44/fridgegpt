<?php

namespace App\Http\Controllers\Api\V1\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for Stripe-related operations.
 */
class StripeController extends Controller
{
    /**
     * Create a Stripe SetupIntent for collecting payment method.
     */
    public function createSetupIntent(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->createOrGetStripeCustomer();

        $intent = $user->createSetupIntent();

        return response()->json([
            'client_secret' => $intent->client_secret,
        ]);
    }
}
