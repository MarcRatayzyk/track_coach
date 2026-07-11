<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Resend API Key
    |--------------------------------------------------------------------------
    |
    | Render uses RESEND_KEY; the official package default is RESEND_API_KEY.
    | Both are supported here.
    |
    */

    'api_key' => env('RESEND_API_KEY', env('RESEND_KEY')),

    'domain' => env('RESEND_DOMAIN'),

    'path' => env('RESEND_PATH', 'resend'),

    'webhook' => [
        'secret' => env('RESEND_WEBHOOK_SECRET'),
        'tolerance' => env('RESEND_WEBHOOK_TOLERANCE', 300),
    ],

];
