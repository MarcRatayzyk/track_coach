<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Éditeur du site (LCEN)
    |--------------------------------------------------------------------------
    |
    | Complète ces valeurs dans .env ou directement ici une fois ton statut
    | juridique défini (auto-entrepreneur, SASU, etc.).
    |
    */

    'publisher' => [
        'name' => env('LEGAL_PUBLISHER_NAME', 'Information coming soon'),
        'legal_form' => env('LEGAL_PUBLISHER_FORM', 'Information coming soon'),
        'address' => env('LEGAL_PUBLISHER_ADDRESS', 'Information coming soon'),
        'siret' => env('LEGAL_SIRET', 'XXXX'),
        'rcs' => env('LEGAL_RCS', 'XXXX'),
        'vat' => env('LEGAL_VAT', 'XXXX'),
        'capital' => env('LEGAL_CAPITAL', 'XXXX'),
        'director' => env('LEGAL_DIRECTOR', 'Information coming soon'),
        'email' => env('LEGAL_EMAIL', 'contact@powerroster.fr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hébergeur
    |--------------------------------------------------------------------------
    */

    'hosting' => [
        'name' => 'OVH SAS',
        'address' => '2 rue Kellermann, 59100 Roubaix, France',
        'phone' => '1007',
        'website' => 'https://www.ovh.com',
    ],

];
