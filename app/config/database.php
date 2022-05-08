<?php

declare(strict_types = 1);

use Cycle\Database\Config;

return [
    'default'   => 'default',
    'databases' => [
        'default' => ['driver' => 'default'],
    ],
    'drivers'   => [
        'default' => new Config\MySQLDriverConfig(
            connection: new Config\MySQL\DsnConnectionConfig(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', env('DB_HOST'), env('DB_NAME')),
                env('DB_USER'),
                env('DB_PASSWORD'),
                [
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4"',
                ]
            ),
            queryCache: true,
        ),
    ],
];
