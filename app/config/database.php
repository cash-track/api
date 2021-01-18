<?php

declare(strict_types = 1);

use Spiral\Database\Driver;

return [
    'default'   => 'default',
    'databases' => [
        'default' => ['driver' => 'default'],
//        'old' => ['driver' => 'old'],
    ],
    'drivers'   => [
        'default' => [
            'driver'     => Driver\MySQL\MySQLDriver::class,
            'connection' => sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', env('DB_HOST'), env('DB_NAME')),
            'username'   => env('DB_USER'),
            'password'   => env('DB_PASSWORD'),
            'options' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4"',
            ],
        ],
        'old' => [
            'driver'     => Driver\MySQL\MySQLDriver::class,
            'connection' => sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', env('DB_OLD_HOST'), env('DB_OLD_NAME')),
            'username'   => env('DB_OLD_USER'),
            'password'   => env('DB_OLD_PASSWORD'),
            'options' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4"',
            ],
        ],
    ],
];
