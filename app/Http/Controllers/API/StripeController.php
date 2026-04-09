<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->createOrGetStripeCustomer();
        $setupIntent = $user->createSetupIntent();

        return response()->json([
            'clientSecret' => $setupIntent->client_secret,
        ]);
    }
}
