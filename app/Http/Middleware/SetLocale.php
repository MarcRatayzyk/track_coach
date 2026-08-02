<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const COOKIE = 'pr_locale';

    public const SUPPORTED = ['fr', 'en'];

    public const DEFAULT = 'fr';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie(self::COOKIE, self::DEFAULT);

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = self::DEFAULT;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
