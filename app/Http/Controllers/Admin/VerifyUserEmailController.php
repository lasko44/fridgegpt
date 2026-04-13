<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Admin action: manually mark a user's email as verified.
 */
class VerifyUserEmailController extends Controller
{
    public function __invoke(User $user): RedirectResponse
    {
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return back()->with('flash', ['success' => 'Email marked verified']);
    }
}
