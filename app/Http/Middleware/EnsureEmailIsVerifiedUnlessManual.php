<?php

namespace App\Http\Middleware;

use App\Support\ActivationDelivery;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified as BaseEnsureEmailIsVerified;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerifiedUnlessManual
{
    public function handle(Request $request, Closure $next, ?string $redirectToRoute = null): Response
    {
        if (ActivationDelivery::usesManualLinks()) {
            return $next($request);
        }

        return app(BaseEnsureEmailIsVerified::class)->handle($request, $next, $redirectToRoute);
    }
}
