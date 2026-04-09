<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tokens\TokenCheckoutRequest;
use Illuminate\Http\JsonResponse;

class TokenCheckoutController extends Controller
{
    public function __invoke(TokenCheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $package = $request->getPackage();

        $user->createOrGetStripeCustomer();

        $checkout = $user->checkout([$package->stripe_price_id => 1], [
            'success_url' => route('tokens.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('tokens.index'),
            'metadata' => [
                'type' => 'token_purchase',
                'user_id' => $user->id,
                'package_id' => $package->id,
            ],
        ]);

        return response()->json([
            'checkout_url' => $checkout->url,
        ]);
    }
}
