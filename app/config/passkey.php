<?php

declare(strict_types = 1);

use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;

return [
    'service' => [
        'id' => env('AUTH_PASSKEY_SERVICE_ID', 'cash-track.app'),
        'name' => env('AUTH_PASSKEY_SERVICE_NAME', 'Cash Track'),
    ],
    'timeout' => (int) env('AUTH_PASSKEY_TIMEOUT', '300000'), // 5 minutes by default
    'algorithms' => [
        ES256K::create(),
        ES256::create(), // "ES256" as registered in the IANA COSE Algorithms registry
        RS256::create(), // Value registered by the specification for "RS256"
    ],
];
