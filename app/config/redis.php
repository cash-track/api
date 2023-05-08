<?php

declare(strict_types = 1);

return [
    /**
     * Can be a host, or the path to a unix domain socket.
     * Starting from version 5.0.0 it is possible to specify schema.
     */
    'host' => env('REDIS_HOST', 'localhost'),
    'port' => env('REDIS_PORT', 6379),

    /**
     * Value in seconds (default is 0 meaning it will use default_socket_timeout)
     */
    'timeout' => 2.0,

    /**
     * Value in milliseconds
     */
    'retry_interval' => 2,

    /**
     * Value in seconds (default is 0 meaning it will use default_socket_timeout)
     */
    'retry_timeout' => 2.0,

    /**
     * Prepend to any key on a connection level
     */
    'prefix' => 'CT:',

    /**
     * The number of retries, meaning if you set this option to n, there will be a maximum n+1 attempts overall.
     */
    'max_retries' => 5,
];
