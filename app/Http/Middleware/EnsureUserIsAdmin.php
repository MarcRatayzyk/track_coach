<?php

namespace App\Http\Middleware;

use App\Support\AuthRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            if ($user) {
                return redirect()->to(AuthRedirect::homeUrl($user));
            }

            abort(403, 'Cette section est réservée aux administrateurs.');
        }

        if ($user->disabled_at !== null) {
            abort(403, 'Compte désactivé.');
        }

        return $next($request);
    }
}
