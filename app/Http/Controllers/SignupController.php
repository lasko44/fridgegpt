<?php

namespace App\Http\Controllers;

use App\Facades\ModelSlugger;
use App\Http\Requests\SignupRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laravel\Socialite\Facades\Socialite;

class SignupController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('Signup');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SignupRequest $request): RedirectResponse
    {

        $user = User::create([
            'name' => $request->name,
            'username' => ModelSlugger::slug(User::class, $request->name, 'username'),
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        auth()->login($user);
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
     * Handle Google callback.
     */
    public function googleCallback(): RedirectResponse
    {
        $googleUser = Socialite::driver('google')->user();
        $name = $googleUser->getName() ?: 'Google User';
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            ['name' => $name, 'password' => Hash::make(Str::random(24))]
        );
        auth()->login($user);
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
     * Handle Facebook callback.
     */
    public function facebookCallback(): RedirectResponse
    {
        $fbUser = Socialite::driver('facebook')->user();
        $name = $fbUser->getName() ?: 'Facebook User';
        $user = User::firstOrCreate(
            ['email' => $fbUser->getEmail()],
            ['name' => $name, 'password' => Hash::make(Str::random(24))]
        );
        auth()->login($user);
        return redirect()->intended(route('home'));
    }
}