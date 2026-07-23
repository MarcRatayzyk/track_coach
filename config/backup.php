<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backup destination
    |--------------------------------------------------------------------------
    |
    | Absolute directory where backup archives are written. Defaults to
    | storage/app/backups. Point this to a mounted volume in production.
    |
    */

    'destination' => env('BACKUP_DESTINATION', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Number of most recent backup archives to keep. Older ones are pruned.
    |
    */

    'keep' => (int) env('BACKUP_KEEP', 7),

    /*
    |--------------------------------------------------------------------------
    | Include public storage
    |--------------------------------------------------------------------------
    |
    | When true, files under storage/app/public are added to the archive.
    | Videos stored on S3/R2 are not included (they live outside the app).
    |
    */

    'include_public_storage' => (bool) env('BACKUP_INCLUDE_STORAGE', true),

];
