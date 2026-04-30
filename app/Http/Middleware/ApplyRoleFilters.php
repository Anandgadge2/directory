<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ApplyRoleFilters
{
    /**
     * Handle an incoming request.
     * This middleware applies role-based data filtering to queries
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Store role-based filters in the request
            if ($user->isSupervisor()) {
                // Supervisors can only see guards assigned to them
                $guardIds = \DB::table('site_assign')
                    ->where('supervisor_id', $user->id)
                    ->pluck('user_id')
                    ->toArray();
                
                $request->merge(['role_filter_guard_ids' => $guardIds]);
            }

            // Store user role for easy access
            $request->merge(['user_role_id' => $user->role_id]);
        }

        return $next($request);
    }
}
