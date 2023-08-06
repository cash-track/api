<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class FirebaseConfig extends InjectableConfig
{
    public const CONFIG = 'firebase';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'databaseUri'             => null,
        'storageBucket'           => null,
        'projectId'               => null,
        'privateKeyId'            => null,
        'privateKey'              => null,
        'clientEmail'             => null,
        'clientId'                => null,
        'authUri'                 => null,
        'tokenUri'                => null,
        'authProviderX509CertUrl' => null,
        'clientX509CertUrl'       => null,
    ];

    public function getDatabaseUri(): string
    {
        return (string) $this->config['databaseUri'];
    }

    public function getStorageBucket(): string
    {
        return (string) $this->config['storageBucket'];
    }

    public function getProjectId(): string
    {
        return (string) $this->config['projectId'];
    }

    public function getPrivateKeyId(): string
    {
        return (string) $this->config['privateKeyId'];
    }

    public function getPrivateKey(): string
    {
        return base64_decode($this->config['privateKey']);
    }

    public function getClientEmail(): string
    {
        return (string) $this->config['clientEmail'];
    }

    public function getClientId(): string
    {
        return (string) $this->config['clientId'];
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

    public function getClientX509CertUrl(): string
    {
        return (string) $this->config['clientX509CertUrl'];
    }
}
