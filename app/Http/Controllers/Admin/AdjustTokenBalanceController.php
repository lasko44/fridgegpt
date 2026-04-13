<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin action: manually credit or debit a user's token balance with a reason.
 */
class AdjustTokenBalanceController extends Controller
{
    public function __invoke(Request $request, User $user, TokenService $tokens): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $admin = $request->user();
        $reason = "[admin: {$admin->name}] {$validated['reason']}";

        if ($validated['amount'] > 0) {
            $tokens->creditBonus($user, $validated['amount'], $reason);
        } else {
            $tokens->refund($user, abs($validated['amount']), $reason);
        }

        return back()->with('flash', [
            'success' => "Adjusted balance by {$validated['amount']} tokens",
        ]);
    }
}
