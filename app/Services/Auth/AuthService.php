<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Service for handling authentication-related business logic.
 */
class AuthService
{
    /**
     * Attempt to authenticate a user with credentials.
     *
     * @param array{email: string, password: string} $credentials
     * @param bool $remember
     * @return User
     * @throws ValidationException
     */
    public function attemptLogin(array $credentials, bool $remember = false): User
    {
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        request()->session()->regenerate();

        return Auth::user();
    }

    /**
     * Log out the current user.
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    /**
     * Create a new user account.
     *
     * @param array{name: string, email: string, password: string} $data
     * @return User
     */
    public function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Login a user directly (without credentials check).
     */
    public function loginUser(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
    }

    /**
     * Create an API token for mobile authentication.
     *
     * @param User $user
     * @param string $tokenName
     * @param array<string> $abilities
     * @return string
     */
    public function createApiToken(User $user, string $tokenName = 'mobile-app', array $abilities = ['*']): string
    {
        return $user->createToken($tokenName, $abilities)->plainTextToken;
    }

    /**
     * Revoke all API tokens for a user.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Revoke the current API token.
     */
    public function revokeCurrentToken(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
