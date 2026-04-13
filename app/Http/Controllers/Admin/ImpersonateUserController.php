<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin impersonation — log in as another user. Stores the original admin ID
 * in the session so we can stop impersonating later.
 */
class ImpersonateUserController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $request->session()->put('impersonator_id', Auth::id());
        Auth::login($user);

        return redirect('/')->with('flash', [
            'info' => "You are now viewing as {$user->name}",
        ]);
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');

        if (! $impersonatorId) {
            return redirect('/');
        }

        $admin = User::find($impersonatorId);
        if ($admin) {
            Auth::login($admin);
        }

        return redirect('/admin')->with('flash', ['success' => 'Stopped impersonating']);
    }
}
