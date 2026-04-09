<?php

namespace App\Http\Controllers\Tokens;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Inertia\Inertia;
use Inertia\Response;

class TokenController extends Controller
{
    public function index(TokenService $tokenService): Response
    {
        $user = auth()->user();

        return Inertia::render('Tokens', [
            'packages' => $tokenService->getPackages(),
            'balance' => $tokenService->getBalance($user),
            'history' => $tokenService->getHistory($user, 20),
            'stats' => $tokenService->getUsageStats($user),
        ]);
    }

    public function success(): Response
    {
        return Inertia::render('TokenSuccess');
    }
}
