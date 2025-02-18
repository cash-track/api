<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

final class S3Config extends InjectableConfig
{
    public const string CONFIG = 's3';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'region'   => null,
        'endpoint' => null,
        'key'      => null,
        'secret'   => null,
    ];

    public function getRegion(): string
    {
        return (string) $this->config['region'];
    }

    public function getEndpoint(): string
    {
        return (string) $this->config['endpoint'];
    }

    public function getKey(): string
    {
        return (string) $this->config['key'];
    }

    public function getSecret(): string
    {
        return (string) $this->config['secret'];
    }
}
