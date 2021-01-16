<?php

declare(strict_types = 1);

use Spiral\Database\Driver;

return [
    'default'   => 'default',
    'databases' => [
        'default' => ['driver' => 'default'],
        'old' => ['driver' => 'old'],
    ],
    'drivers'   => [
        'default' => [
            'driver'     => Driver\MySQL\MySQLDriver::class,
            'connection' => sprintf('mysql:host=%s;dbname=%s', env('DB_HOST'), env('DB_NAME')),
            'username'   => env('DB_USER'),
            'password'   => env('DB_PASSWORD'),
        ],
        'old' => [
            'driver'     => Driver\MySQL\MySQLDriver::class,
            'connection' => sprintf('mysql:host=%s;dbname=%s', env('DB_OLD_HOST'), env('DB_OLD_NAME')),
            'username'   => env('DB_OLD_USER'),
            'password'   => env('DB_OLD_PASSWORD'),
        ],
    ],
];
