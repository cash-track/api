<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

final class CdnConfig extends InjectableConfig
{
    public const string CONFIG = 'cdn';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'host'   => null,
        'bucket' => null
    ];

    public function getHost(): string
    {
        return (string) $this->config['host'];
    }

    public function getBucket(): string
    {
        return (string) $this->config['bucket'];
    }
}
