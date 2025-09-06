<?php
// app/Http/Middleware/CheckEditPermission.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckEditPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Check if user can edit (admin or planner)
        if (!$user->canEdit()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied. You do not have permission to edit data.'], 403);
            }
            abort(403, 'Access denied. You do not have permission to edit data.');
        }

        return $next($request);
    }
}