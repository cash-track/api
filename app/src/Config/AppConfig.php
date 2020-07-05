<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class AppConfig extends InjectableConfig
{
    public const CONFIG = 'app';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected $config = [
        'url' => '',
    ];

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->config['url'];
    }
}
