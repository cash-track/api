<?php

declare(strict_types = 1);

return [
    'secret'           => env('JWT_SECRET'),
    'ttl'              => env('JWT_TTL', 3600),
    'refreshTtl'       => env('JWT_REFRESH_TTL', 604800),
    'publicKey'        => env('JWT_PUBLIC_KEY'),
    'privateKey'       => env('JWT_PRIVATE_KEY'),
    'issuer'           => env('JWT_ISSUER', 'https://api.cash-track.app'),
    'audience'         => env('JWT_AUDIENCE', 'https://api.cash-track.app'),
    'accessPublicKey'  => env('ACCESS_TOKEN_PUBLIC_KEY'),
    'accessPrivateKey' => env('ACCESS_TOKEN_PRIVATE_KEY'),
];
