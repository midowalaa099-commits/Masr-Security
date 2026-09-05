<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is an administrator when accessing /admin.
 * Server-side only; never relies on hidden links in the UI.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest() || ! Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
