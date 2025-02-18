<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class GoogleApiConfig extends InjectableConfig
{
    public const string CONFIG = 'google';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'clientId'                => null,
        'projectId'               => null,
        'authUri'                 => null,
        'tokenUri'                => null,
        'authProviderX509CertUrl' => null,
        'clientSecret'            => null,
        'redirectUris'            => null,
    ];

    public function getClientId(): string
    {
        return (string) $this->config['clientId'];
    }

    public function getProjectId(): string
    {
        return (string) $this->config['projectId'];
    }

    public function getAuthUri(): string
    {
        return (string) $this->config['authUri'];
    }

    public function getTokenUri(): string
    {
        return (string) $this->config['tokenUri'];
    }

    public function getAuthProviderX509CertUrl(): string
    {
        return (string) $this->config['authProviderX509CertUrl'];
    }

    public function getClientSecret(): string
    {
        return (string) $this->config['clientSecret'];
    }

    public function getRedirectUris(): array
    {
        return (array) $this->config['redirectUris'];
    }
}
