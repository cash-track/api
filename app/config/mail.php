<?php

declare(strict_types = 1);

return [
    'driver' => env('MAIL_DRIVER', 'smtp'),

    'drivers' => [
        'smtp' => [
            'host'       => env('MAIL_HOST'),
            'port'       => env('MAIL_PORT'),
            'username'   => env('MAIL_USERNAME'),
            'password'   => env('MAIL_PASSWORD'),
            'encryption' => env('MAIL_ENCRYPTION')
        ],
    ],
];
