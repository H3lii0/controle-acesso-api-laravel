<?php

return [
    'name' => env('SCHOOL_NAME', 'Escola Modelo'),
    'timezone' => env('SCHOOL_TIMEZONE', 'America/Recife'),

    'central_administrator' => [
        'name' => env('CENTRAL_ADMIN_NAME'),
        'email' => env('CENTRAL_ADMIN_EMAIL'),
        'password' => env('CENTRAL_ADMIN_PASSWORD'),
    ],
];
