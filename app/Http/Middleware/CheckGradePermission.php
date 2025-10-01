<?php
// app/Http/Middleware/CheckGradePermission.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckGradePermission
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        if (!$user->canGrade()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Access denied. You do not have permission to grade.'], 403);
            }
            abort(403, 'Access denied. You do not have permission to grade.');
        }

        return $next($request);
    }
}
