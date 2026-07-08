<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Admin account
    |--------------------------------------------------------------------------
    |
    | A single administrator account is seeded from these values so the quiz
    | admin panel can be reached in every environment. Set them in your .env.
    |
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'TCL Admin'),
        'email' => env('ADMIN_EMAIL', ''),
        'password' => env('ADMIN_PASSWORD', ''),
    ],
];
