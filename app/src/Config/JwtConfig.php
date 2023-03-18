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

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return (string) $this->config['secret'];
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return (int) $this->config['ttl'];
    }

    /**
     * @return int
     */
    public function getRefreshTtl(): int
    {
        return (int) $this->config['refreshTtl'];
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return (string) base64_decode((string) $this->config['publicKey']);
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return (string) base64_decode((string) $this->config['privateKey']);
    }
}
