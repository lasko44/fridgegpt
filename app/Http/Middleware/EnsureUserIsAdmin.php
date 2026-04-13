<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Restricts access to admin-only routes. Returns 404 instead of 403 to avoid
 * leaking the existence of admin endpoints (per ADR-005).
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->is_admin) {
            throw new NotFoundHttpException();
        }

        return $next($request);
    }
}
