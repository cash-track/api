<?php

declare(strict_types=1);

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'roadrunner' => [
            'driver' => 'roadrunner',
            'pipeline' => 'low-priority',
            'default' => 'low-priority',
        ],
        'roadrunner-high' => [
            'driver' => 'roadrunner',
            'pipeline' => 'high-priority',
        ],
    ],
];
