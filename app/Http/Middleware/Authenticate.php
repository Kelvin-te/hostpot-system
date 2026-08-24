<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Redirect staff-guard failures to the staff login page instead of the customer login.
     */
    protected function unauthenticated($request, array $guards)
    {
        if (! $request->expectsJson() && in_array('staff', $guards, true)) {
            throw new AuthenticationException(
                'Unauthenticated.', $guards, route('staff.login')
            );
        }

        parent::unauthenticated($request, $guards);
    }
}
