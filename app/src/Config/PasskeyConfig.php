<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class PasskeyConfig extends InjectableConfig
{
    public const CONFIG = 'passkey';
    public const DEFAULT_TIMEOUT = 300000;

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'service' => [
            'id' => '',
            'name' => '',
        ],
        'timeout' => 0,
        'supported' => [],
    ];

    public function getServiceId(): string
    {
        return $this->config['service']['id'] ?? '';
    }

    public function getServiceName(): string
    {
        return $this->config['service']['name'] ?? '';
    }

    /**
     * @return positive-int
     */
    public function getTimeout(): int
    {
        $timeout = (int) ($this->config['timeout'] ?? 0);
        return $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * @return \Webauthn\PublicKeyCredentialParameters[]
     */
    public function getSupported(): array
    {
        return $this->config['supported'] ?? [];
    }
}
