<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class CdnConfig extends InjectableConfig
{
    public const CONFIG = 'cdn';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'host'   => null,
        'bucket' => null
    ];

    /**
     * @return string
     */
    public function getHost(): string
    {
        return (string) $this->config['host'];
    }

    /**
     * @return string
     */
    public function getBucket(): string
    {
        return (string) $this->config['bucket'];
    }
}
