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

];
