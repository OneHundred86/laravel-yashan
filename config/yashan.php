<?php

return [
    'yashan' => [
        'driver'         => 'yashan',
        'host'           => env('DB_HOST', '127.0.0.1'),
        'port'           => env('DB_PORT', '1688'),
        'database'       => env('DB_DATABASE', 'test'),
        'username'       => env('DB_USERNAME', 'test'),
        'password'       => env('DB_PASSWORD', 'test'),
        'charset'        => env('DB_CHARSET', 'UTF8'),
        'prefix'         => env('DB_PREFIX', ''),
    ],
];
