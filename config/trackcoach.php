<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manual activation links (beta)
    |--------------------------------------------------------------------------
    |
    | When true, activation and invitation e-mails are not sent. Coaches copy
    | signed setup links and share them manually (WhatsApp, SMS, etc.).
    | Set MANUAL_ACTIVATION_LINKS=false when transactional e-mail is ready.
    |
    */

    'manual_activation_links' => env('MANUAL_ACTIVATION_LINKS', true),

    /*
    |--------------------------------------------------------------------------
    | PostHog (product analytics)
    |--------------------------------------------------------------------------
    |
    | Runtime keys (preferred on Render/Docker). Vite VITE_POSTHOG_* is only a
    | local-dev fallback — Docker builds often miss build-time env vars.
    |
    */

    'posthog' => [
        'key' => env('POSTHOG_KEY', env('VITE_POSTHOG_KEY')),
        'host' => env('POSTHOG_HOST', env('VITE_POSTHOG_HOST', 'https://eu.i.posthog.com')),
        'ui_host' => env('POSTHOG_UI_HOST', 'https://eu.posthog.com'),
    ],

];
