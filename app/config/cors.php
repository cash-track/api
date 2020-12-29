<?php

declare(strict_types = 1);

return [
    'allowedOrigins' => explode(',', env('CORS_ALLOWED_ORIGINS')),
    'allowedOriginsPatterns' => [],
    'supportsCredentials' => false,
    'allowedHeaders' => ['*'],
    'exposedHeaders' => [],
    'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'maxAge' => 600,
];
