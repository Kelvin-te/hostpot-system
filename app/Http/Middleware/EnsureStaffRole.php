<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to staff with a given role.
 * Usage: ->middleware('staff.role:admin')
 */
class EnsureStaffRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $staff = $request->user('staff');

        if (!$staff || $staff->role !== $role) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
