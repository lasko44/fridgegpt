<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TokenCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin coupon/promo code management.
 */
class TokenCodeController extends Controller
{
    public function index(): Response
    {
        $codes = TokenCode::query()
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Tokens/Codes', [
            'codes' => $codes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:token_codes,code'],
            'tokens' => ['required', 'integer', 'min:1', 'max:10000'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'type' => ['required', 'in:promo,bonus,referral'],
        ]);

        TokenCode::create([
            'code' => strtoupper($validated['code'] ?? Str::random(8)),
            'type' => $validated['type'],
            'tokens' => $validated['tokens'],
            'max_uses' => $validated['max_uses'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('flash', ['success' => 'Code created']);
    }

    public function update(Request $request, TokenCode $code): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $code->update($validated);

        return back()->with('flash', ['success' => 'Code updated']);
    }

    public function destroy(TokenCode $code): RedirectResponse
    {
        $code->delete();

        return back()->with('flash', ['success' => 'Code deleted']);
    }
}
