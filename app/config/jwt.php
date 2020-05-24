<?php

declare(strict_types=1);

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => env('JWT_TTL', 3600),
    'refreshTtl' => env('JWT_REFRESH_TTL', 604800),
];
