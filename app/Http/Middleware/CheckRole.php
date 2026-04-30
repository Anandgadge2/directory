<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user's role is in the allowed roles
        foreach ($roles as $role) {
            if ($user->role_id == $role) {
                return $next($request);
            }
        }

        // If user doesn't have the required role, redirect with error
        abort(403, 'Unauthorized access. You do not have permission to view this page.');
    }
}
