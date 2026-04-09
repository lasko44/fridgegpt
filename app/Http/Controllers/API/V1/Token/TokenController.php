<?php

namespace App\Http\Controllers\Api\V1\Token;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tokens\TokenCheckoutRequest;
use App\Services\TokenService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request, TokenService $tokenService): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'packages' => $tokenService->getPackages(),
            'balance' => $tokenService->getBalance($user),
            'history' => $tokenService->getHistory($user, 20),
            'stats' => $tokenService->getUsageStats($user),
        ]);
    }

    public function balance(Request $request): JsonResponse
    {
        return response()->json([
            'balance' => $request->user()->token_balance,
        ]);
    }

    public function checkout(TokenCheckoutRequest $request): JsonResponse
    {
        $user = $request->user();
        $package = $request->getPackage();

        $user->createOrGetStripeCustomer();

        $checkout = $user->checkout([$package->stripe_price_id => 1], [
            'success_url' => config('app.url') . '/tokens/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/tokens',
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

    public function redeem(Request $request, TokenService $tokenService): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:50']);

        try {
            $result = $tokenService->redeemCode($request->user(), $request->input('code'));
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
