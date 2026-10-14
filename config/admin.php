<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super Admin (seed & bootstrap)
    |--------------------------------------------------------------------------
    |
    | Dipakai AdminUserSeeder. Baca via config(), bukan env() di runtime,
    | agar tetap benar saat `php artisan config:cache`.
    |
    */

    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
        'email' => env('SUPER_ADMIN_EMAIL', 'dedi@intilab.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'skyhwk12'),
    ],

];
