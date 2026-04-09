<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Facades\ModelSlugger;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignupRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * API controller for handling user registration.
 */
class RegisterController extends Controller
{
    /**
     * Handle a registration request and return API token.
     */
    public function store(SignupRequest $request, AuthService $authService): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->name,
            'username' => ModelSlugger::slug(User::class, $request->name, 'username'),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $authService->createApiToken($user);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}
