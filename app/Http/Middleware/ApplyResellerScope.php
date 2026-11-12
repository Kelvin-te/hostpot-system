<?php

namespace App\Http\Middleware;

use App\Scopes\ResellerScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Activate reseller data scoping when the logged-in staff member is a
 * reseller. Admin staff and non-staff requests are unaffected.
 */
class ApplyResellerScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = $request->user('staff');

        ResellerScope::set(
            $staff && $staff->isReseller() ? $staff->reseller_id : null
        );

        return $next($request);
    }
}
