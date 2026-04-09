<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TokenCodeController extends Controller
{
    public function __invoke(Request $request, TokenService $tokenService): RedirectResponse
    {
        $request->validate(['code' => 'required|string|max:50']);

        try {
            $result = $tokenService->redeemCode($request->user(), $request->input('code'));

            return redirect()->route('tokens.index')
                ->with('flash', ['success' => $result['message']]);
        } catch (Exception $e) {
            return redirect()->route('tokens.index')
                ->withErrors(['code' => $e->getMessage()]);
        }
    }
}
