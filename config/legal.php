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
        'name' => env('LEGAL_PUBLISHER_NAME', '[À compléter — nom / raison sociale]'),
        'legal_form' => env('LEGAL_PUBLISHER_FORM', '[À compléter — ex. auto-entrepreneur, SASU]'),
        'address' => env('LEGAL_PUBLISHER_ADDRESS', '[À compléter — adresse postale]'),
        'siret' => env('LEGAL_SIRET', '[À compléter]'),
        'rcs' => env('LEGAL_RCS', '[À compléter — ville du RCS, ou « Non applicable »]'),
        'vat' => env('LEGAL_VAT', '[À compléter — n° TVA intracommunautaire, ou « Non applicable »]'),
        'capital' => env('LEGAL_CAPITAL', '[À compléter — capital social, ou « Non applicable »]'),
        'director' => env('LEGAL_DIRECTOR', '[À compléter — directeur de publication]'),
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
