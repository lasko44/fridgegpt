<?php

namespace App\Http\Controllers;

use App\Facades\ModelSlugger;
use App\Http\Requests\SignupRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Auth\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

/**
 * Controller for handling user registration web requests.
 */
class SignupController extends Controller
{
    /**
     * Display the registration form.
     */
    public function index(): Response
    {
        return inertia('Signup');
    }

    /**
     * Handle a registration request.
     */
    public function store(SignupRequest $request, AuthService $authService): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->name,
            'username' => ModelSlugger::slug(User::class, $request->name, 'username'),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $authService->loginUser($user);

        return redirect()->intended(route('home'));
    }

    /**
     * Redirect to Google for authentication.
     */
    public function googleRedirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function googleCallback(SocialAuthService $socialAuthService, AuthService $authService): RedirectResponse
    {
        $user = $socialAuthService->handleGoogleCallback();
        $authService->loginUser($user);

        return redirect()->intended(route('home'));
    }

    /**
     * Redirect to Facebook for authentication.
     */
    public function facebookRedirect(): RedirectResponse
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Handle Facebook OAuth callback.
     */
    public function facebookCallback(SocialAuthService $socialAuthService, AuthService $authService): RedirectResponse
    {
        $user = $socialAuthService->handleFacebookCallback();
        $authService->loginUser($user);

        return redirect()->intended(route('home'));
    }
}
