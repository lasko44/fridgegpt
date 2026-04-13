<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Mobile API admin impersonation — generates a token for the target user
 * so the admin can see the app through their eyes.
 */
class ImpersonateController extends Controller
{
    public function start(Request $request, string $uuid): JsonResponse
    {
        $admin = $request->user();

        if (! $admin?->is_admin) {
            throw new NotFoundHttpException();
        }

        $target = User::where('uuid', $uuid)->firstOrFail();

        $token = $target->createToken('impersonation')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'uuid' => $target->uuid,
                'name' => $target->name,
                'email' => $target->email,
                'token_balance' => $target->token_balance,
            ],
            'admin_token' => $request->bearerToken(),
        ]);
    }
}
