<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Config\JwtConfig;
use Firebase\JWT\JWT;

final class JwksService
{
    public function __construct(
        private readonly JwtConfig $config,
    ) {
    }

    /**
     * RFC 7517 JWK Set for the access token RSA public key. Returns an empty key set — never an
     * error, and never the HMAC secret — when no RSA keypair is configured yet.
     *
     * @return array{keys: list<array{kty: string, use: string, alg: string, kid: string, n: string, e: string}>}
     */
    public function getKeySet(): array
    {
        $publicKey = $this->config->getAccessPublicKey();

        if ($publicKey === '') {
            return ['keys' => []];
        }

        $key = openssl_pkey_get_public($publicKey);

        if ($key === false) {
            return ['keys' => []];
        }

        $details = openssl_pkey_get_details($key);

        if ($details === false || ! isset($details['rsa']['n'], $details['rsa']['e'])) {
            return ['keys' => []];
        }

        return [
            'keys' => [[
                'kty' => 'RSA',
                'use' => 'sig',
                'alg' => 'RS256',
                'kid' => $this->config->getAccessKeyId(),
                'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
                'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
            ]],
        ];
    }
}
