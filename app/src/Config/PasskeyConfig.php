<?php

declare(strict_types=1);

namespace App\Config;

use Cose\Algorithm\Algorithm;
use Spiral\Core\InjectableConfig;
use Webauthn\PublicKeyCredentialParameters;

final class PasskeyConfig extends InjectableConfig
{
    public const string CONFIG = 'passkey';
    public const int DEFAULT_TIMEOUT = 300000;

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'service' => [
            'id' => '',
            'name' => '',
        ],
        'timeout' => 0,
        'algorithms' => [],
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
    public function getSupportedPublicKeyCredentials(): array
    {
        return array_map(
            fn(Algorithm $algorithm) => PublicKeyCredentialParameters::create('public-key', $algorithm::identifier()),
            $this->config['algorithms'] ?? [],
        );
    }

    /**
     * @return \Cose\Algorithm\Algorithm[]
     */
    public function getSupportedAlgorithms(): array
    {
        return $this->config['algorithms'] ?? [];
    }
}
