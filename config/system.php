<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Administrator Configuration
    |--------------------------------------------------------------------------
    |
    | These values are used ONLY during the initial installation of the system
    | to create the first administrator account. Once the admin changes their
    | password, these values are never used again.
    |
    */

    'admin' => [
        'name'     => env('SYSTEM_ADMIN_NAME', 'Administrador'),
        'email'    => env('SYSTEM_ADMIN_EMAIL', 'administrador@cafedecolombia.com'),
        'password' => env('SYSTEM_ADMIN_PASSWORD', 'Comite2026*'),
        'document' => env('SYSTEM_ADMIN_DOCUMENT', '0000000000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Password Change on First Login
    |--------------------------------------------------------------------------
    |
    | When true, newly created admin accounts via the seeder will be required
    | to change their password on the first login.
    |
    */

    'force_password_change' => env('SYSTEM_FORCE_PASSWORD_CHANGE', true),

];
