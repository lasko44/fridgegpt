<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for handling user logout.
 */
class LogoutController extends Controller
{
    /**
     * Handle a logout request and revoke current token.
     */
    public function destroy(Request $request, AuthService $authService): JsonResponse
    {
        $authService->revokeCurrentToken($request->user());

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}
