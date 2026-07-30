<?php

namespace App\Http\Middleware;

use App\Support\BillingAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCoachHasBillingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (BillingAccess::hasAppAccess($user)) {
            return $next($request);
        }

        if ($user->role === 'coach') {
            if ($request->routeIs('billing.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('billing.index');
        }

        if ($user->role === 'athlete') {
            if ($request->routeIs('subscription.blocked') || $request->routeIs('logout') || $request->routeIs('account.*')) {
                return $next($request);
            }

            return redirect()->route('subscription.blocked');
        }

        return $next($request);
    }
}
