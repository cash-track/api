<?php

declare(strict_types=1);

use Spiral\Queue\Driver\SyncDriver;
use Spiral\RoadRunnerBridge\Queue\Queue;

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'aliases' => [
         'mail-queue' => 'roadrunner',
    ],

    'pipelines' => [
        'mail' => [
            'connector' => 'roadrunner',
            'consume' => true,
        ],
    ],

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'roadrunner' => [
            'driver' => 'roadrunner',
        ],
    ],

    'driverAliases' => [
        'sync' => SyncDriver::class,
        'roadrunner' => Queue::class,
    ],

    'registry' => [
        'handlers' => [],
    ],
];
