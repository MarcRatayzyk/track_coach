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

        if ($user->role === 'admin') {
            return $next($request);
        }

        if (BillingAccess::hasAppAccess($user)) {
            return $next($request);
        }

        if ($user->role === 'coach') {
            if ($request->routeIs('billing.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            return $this->deny($request, 'billing.index');
        }

        if ($user->role === 'athlete') {
            if ($request->routeIs('subscription.blocked') || $request->routeIs('logout') || $request->routeIs('account.*')) {
                return $next($request);
            }

            return $this->deny($request, 'subscription.blocked');
        }

        // Fail-closed for unknown roles: no app access without a known billed role.
        return $this->denyForbidden($request);
    }

    private function deny(Request $request, string $redirectRoute): Response
    {
        if ($this->wantsJsonDenial($request)) {
            return response()->json([
                'message' => 'Un abonnement actif est requis pour accéder à cette ressource.',
                'code' => 'subscription_required',
            ], 403);
        }

        return redirect()->route($redirectRoute);
    }

    private function denyForbidden(Request $request): Response
    {
        if ($this->wantsJsonDenial($request)) {
            return response()->json([
                'message' => 'Accès refusé.',
                'code' => 'forbidden',
            ], 403);
        }

        abort(403);
    }

    private function wantsJsonDenial(Request $request): bool
    {
        return $request->expectsJson()
            || $request->is('api/*')
            || $request->ajax();
    }
}
