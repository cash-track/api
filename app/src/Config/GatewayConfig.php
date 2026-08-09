<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class GatewayConfig extends InjectableConfig
{
    public const string CONFIG = 'gateway';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'secret' => null,
    ];

    /** Sent by the gateway as X-Gateway-Secret; empty disables the check. */
    public function getSecret(): string
    {
        return (string) $this->config['secret'];
    }
}
