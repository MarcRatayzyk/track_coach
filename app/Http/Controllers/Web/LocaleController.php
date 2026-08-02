<?php

namespace App\Http\Controllers\Web;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => 'required|in:'.implode(',', SetLocale::SUPPORTED),
        ])['locale'];

        Cookie::queue(cookie(
            SetLocale::COOKIE,
            $locale,
            60 * 24 * 365,
            '/',
            null,
            null,
            false,
            false,
            'lax'
        ));

        return back();
    }
}
