<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class JwtConfig extends InjectableConfig
{
    public const CONFIG = 'jwt';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'secret' => null,
        'ttl' => null,
        'refreshTtl' => null,
        'publicKey' => null,
        'privateKey' => null,
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
}
