<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class CorsConfig extends InjectableConfig
{
    public const CONFIG = 'cors';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected $config = [
        'allowedOrigins'         => [],
        'allowedOriginsPatterns' => [],
        'supportsCredentials'    => false,
        'allowedHeaders'         => [],
        'exposedHeaders'         => [],
        'allowedMethods'         => [],
        'maxAge'                 => 0
    ];

    /**
     * @return array|string[]
     */
    public function getAllowedOrigins(): array
    {
        return $this->config['allowedOrigins'];
    }

    /**
     * @return array
     */
    public function getAllowedOriginsPatterns(): array
    {
        return $this->config['allowedOriginsPatterns'];
    }

    /**
     * @return bool
     */
    public function getSupportsCredentials(): bool
    {
        return $this->config['supportsCredentials'];
    }

    /**
     * @return array
     */
    public function getAllowedHeaders(): array
    {
        return $this->config['allowedHeaders'];
    }

    /**
     * @return array
     */
    public function getExposedHeaders(): array
    {
        return $this->config['exposedHeaders'];
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->config['allowedMethods'];
    }

    /**
     * @return int
     */
    public function getMaxAge(): int
    {
        return $this->config['maxAge'];
    }
}
