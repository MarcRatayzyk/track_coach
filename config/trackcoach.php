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
        'key' => env('POSTHOG_KEY') ?: env('VITE_POSTHOG_KEY'),
        'host' => env('POSTHOG_HOST') ?: env('VITE_POSTHOG_HOST', 'https://eu.i.posthog.com'),
        'ui_host' => env('POSTHOG_UI_HOST', 'https://eu.posthog.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Android APK download
    |--------------------------------------------------------------------------
    |
    | Fallback when public/downloads/power-roster.apk is absent (production).
    | Defaults to the latest GitHub Release asset.
    |
    */

    'android_apk_url' => env(
        'ANDROID_APK_URL',
        'https://github.com/MarcRatayzyk/track_coach/releases/latest/download/power-roster.apk',
    ),

    /*
    |--------------------------------------------------------------------------
    | Support / bug reports
    |--------------------------------------------------------------------------
    |
    | Destination inbox for in-app bug and feedback reports.
    |
    */

    'support_email' => env('SUPPORT_EMAIL', 'marc.rzyk@gmail.com'),

];
