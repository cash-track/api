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
    protected $config = [
        'secret' => null,
        'ttl' => null,
        'refreshTtl' => null,
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
}
