<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class JwtConfig extends InjectableConfig
{
    public const string CONFIG = 'jwt';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'secret' => null,
        'ttl' => null,
        'refreshTtl' => null,
        'publicKey' => null,
        'privateKey' => null,
        'issuer' => null,
        'audience' => null,
        'accessPublicKey' => null,
        'accessPrivateKey' => null,
    ];

    public function getSecret(): string
    {
        return (string) $this->config['secret'];
    }

    public function getTtl(): int
    {
        return (int) $this->config['ttl'];
    }

    public function getRefreshTtl(): int
    {
        return (int) $this->config['refreshTtl'];
    }

    public function getPublicKey(): string
    {
        return base64_decode((string) $this->config['publicKey']);
    }

    public function getPrivateKey(): string
    {
        return base64_decode((string) $this->config['privateKey']);
    }

    public function getIssuer(): string
    {
        return (string) $this->config['issuer'];
    }

    public function getAudience(): string
    {
        return (string) $this->config['audience'];
    }

    public function getAccessPublicKey(): string
    {
        return base64_decode((string) $this->config['accessPublicKey']);
    }

    public function getAccessPrivateKey(): string
    {
        return base64_decode((string) $this->config['accessPrivateKey']);
    }

    /** JWT/JWKS `kid` for the current RSA access keypair; empty when none is configured. */
    public function getAccessKeyId(): string
    {
        $publicKey = $this->getAccessPublicKey();

        if ($publicKey === '') {
            return '';
        }

        return substr(hash('sha256', $publicKey), 0, 16);
    }
}
