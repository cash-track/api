<?php

declare(strict_types = 1);

return [
    'region'   => env('S3_REGION'),
    'endpoint' => env('S3_ENDPOINT'),
    'key'      => env('S3_KEY'),
    'secret'   => env('S3_SECRET'),
];
