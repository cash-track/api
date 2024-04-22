<?php

declare(strict_types = 1);

use Cose\Algorithms;
use Webauthn\PublicKeyCredentialParameters;

return [
    'service' => [
        'id' => env('AUTH_PASSKEY_SERVICE_ID', 'cash-track.app'),
        'name' => env('AUTH_PASSKEY_SERVICE_NAME', 'Cash Track'),
    ],
    'timeout' => (int) env('AUTH_PASSKEY_TIMEOUT', '300000'), // 5 minutes by default
    'supported' => [
        PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
        PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256), // "ES256" as registered in the IANA COSE Algorithms registry
        PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256), // Value registered by the specification for "RS256"
    ],
];
