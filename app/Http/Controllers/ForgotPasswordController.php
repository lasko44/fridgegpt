<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Response;
use Inertia\ResponseFactory;

class ForgotPasswordController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('ForgotPassword');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('flash', ['success' => __($status)])
            : back()->withErrors(['error' => __($status)]);
    }
}
