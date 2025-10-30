<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     * Usage: role:admin,accountant
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (!$user) {
            // Redirect to conventional login path; avoids dependency on a named route
            return redirect()->guest('/login');
        }

        $allowed = array_filter(array_map('trim', explode(',', $roles)));
        if (empty($allowed) || $user->hasRole($allowed)) {
            return $next($request);
        }

        abort(403, 'Forbidden');
    }
}
