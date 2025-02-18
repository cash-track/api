<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

final class CorsConfig extends InjectableConfig
{
    public const string CONFIG = 'cors';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
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

    public function getAllowedOriginsPatterns(): array
    {
        return $this->config['allowedOriginsPatterns'];
    }

    public function getSupportsCredentials(): bool
    {
        return $this->config['supportsCredentials'];
    }

    public function getAllowedHeaders(): array
    {
        return $this->config['allowedHeaders'];
    }

    public function getExposedHeaders(): array
    {
        return $this->config['exposedHeaders'];
    }

    public function getAllowedMethods(): array
    {
        return $this->config['allowedMethods'];
    }

    public function getMaxAge(): int
    {
        return $this->config['maxAge'];
    }
}
