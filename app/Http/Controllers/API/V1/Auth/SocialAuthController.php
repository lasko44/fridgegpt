<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for handling social authentication (OAuth).
 */
class SocialAuthController extends Controller
{
    /**
     * Handle Google OAuth callback with ID token.
     */
    public function googleCallback(
        Request $request,
        SocialAuthService $socialAuthService,
        AuthService $authService
    ): JsonResponse {
        $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $user = $socialAuthService->handleMobileOAuthCallback('google', $request->id_token);
        $token = $authService->createApiToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Handle Facebook OAuth callback with access token.
     */
    public function facebookCallback(
        Request $request,
        SocialAuthService $socialAuthService,
        AuthService $authService
    ): JsonResponse {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $user = $socialAuthService->handleMobileOAuthCallback('facebook', $request->access_token);
        $token = $authService->createApiToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}
