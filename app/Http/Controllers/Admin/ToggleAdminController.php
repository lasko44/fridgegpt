<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin action: promote/demote a user's admin status.
 */
class ToggleAdminController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('flash', ['error' => "You can't demote yourself"]);
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        $verb = $user->is_admin ? 'promoted' : 'demoted';

        return back()->with('flash', [
            'success' => "{$user->name} {$verb}",
        ]);
    }
}
