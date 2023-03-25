<?php

declare(strict_types = 1);

use Cycle\Database\Config;

return [
    'logger' => [
        'default' => null,
        'drivers' => [
            // 'runtime' => 'stdout'
        ],
    ],

    /**
     * Default database connection
     */
    'default'   => 'default',

    /**
     * The Spiral/Database module provides support to manage multiple databases
     * in one application, use read/write connections and logically separate
     * multiple databases within one connection using prefixes.
     *
     * To register a new database simply add a new one into
     * "databases" section below.
     */
    'databases' => [
        'default' => [
            'driver' => 'default',
        ],
    ],

    /**
     * Each database instance must have an associated connection object.
     * Connections used to provide low-level functionality and wrap different
     * database drivers. To register a new connection you have to specify
     * the driver class and its connection options.
     */
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
