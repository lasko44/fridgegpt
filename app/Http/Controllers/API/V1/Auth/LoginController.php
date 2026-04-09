<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

/**
 * API controller for handling user login.
 */
class LoginController extends Controller
{
    /**
     * Handle a login request and return API token.
     */
    public function store(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();
        $token = $authService->createApiToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}
