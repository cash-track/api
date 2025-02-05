<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class S3Config extends InjectableConfig
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

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return (string) $this->config['region'];
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return (string) $this->config['endpoint'];
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return (string) $this->config['key'];
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return (string) $this->config['secret'];
    }
}
