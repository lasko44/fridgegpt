<?php

namespace App\Services\Auth;

use App\Facades\ModelSlugger;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

/**
 * Service for handling social authentication (OAuth) business logic.
 */
class SocialAuthService
{
    /**
     * Get the Google OAuth redirect URL.
     */
    public function getGoogleRedirectUrl(): string
    {
        return Socialite::driver('google')->redirect()->getTargetUrl();
    }

    /**
     * Get the Facebook OAuth redirect URL.
     */
    public function getFacebookRedirectUrl(): string
    {
        return Socialite::driver('facebook')->redirect()->getTargetUrl();
    }

    /**
     * Handle Google OAuth callback and return or create user.
     */
    public function handleGoogleCallback(): User
    {
        $socialiteUser = Socialite::driver('google')->user();
        return $this->findOrCreateUser($socialiteUser, 'google');
    }

    /**
     * Handle Facebook OAuth callback and return or create user.
     */
    public function handleFacebookCallback(): User
    {
        $socialiteUser = Socialite::driver('facebook')->user();
        return $this->findOrCreateUser($socialiteUser, 'facebook');
    }

    /**
     * Verify a Google ID token for mobile authentication.
     *
     * @param string $idToken
     * @return SocialiteUser
     */
    public function verifyGoogleIdToken(string $idToken): SocialiteUser
    {
        return Socialite::driver('google')->userFromToken($idToken);
    }

    /**
     * Verify a Facebook access token for mobile authentication.
     *
     * @param string $accessToken
     * @return SocialiteUser
     */
    public function verifyFacebookAccessToken(string $accessToken): SocialiteUser
    {
        return Socialite::driver('facebook')->userFromToken($accessToken);
    }

    /**
     * Find or create a user from social auth data.
     */
    public function findOrCreateUser(SocialiteUser $socialiteUser, string $provider): User
    {
        $name = $socialiteUser->getName() ?: ucfirst($provider) . ' User';

        $user = User::firstOrCreate(
            ['email' => $socialiteUser->getEmail()],
            [
                'name' => $name,
                'username' => ModelSlugger::slug(User::class, $name, 'username'),
                'password' => Hash::make(Str::random(24)),
            ]
        );

        return $user;
    }

    /**
     * Handle mobile OAuth callback with token.
     */
    public function handleMobileOAuthCallback(string $provider, string $token): User
    {
        $socialiteUser = match ($provider) {
            'google' => $this->verifyGoogleIdToken($token),
            'facebook' => $this->verifyFacebookAccessToken($token),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };

        return $this->findOrCreateUser($socialiteUser, $provider);
    }
}
