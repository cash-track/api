<?php

declare(strict_types = 1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class FirebaseConfig extends InjectableConfig
{
    public const CONFIG = 'firebase';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected $config = [
        'databaseUri'             => null,
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

    /**
     * @return string
     */
    public function getDatabaseUri(): string
    {
        return (string) $this->config['databaseUri'];
    }

    /**
     * @return string
     */
    public function getProjectId(): string
    {
        return (string) $this->config['projectId'];
    }

    /**
     * @return string
     */
    public function getPrivateKeyId(): string
    {
        return (string) $this->config['privateKeyId'];
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return (string) $this->config['privateKey'];
    }

    /**
     * @return string
     */
    public function getClientEmail(): string
    {
        return (string) $this->config['clientEmail'];
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return (string) $this->config['clientId'];
    }

    /**
     * @return string
     */
    public function getAuthUri(): string
    {
        return (string) $this->config['authUri'];
    }

    /**
     * @return string
     */
    public function getTokenUri(): string
    {
        return (string) $this->config['tokenUri'];
    }

    /**
     * @return string
     */
    public function getAuthProviderX509CertUrl(): string
    {
        return (string) $this->config['authProviderX509CertUrl'];
    }

    /**
     * @return string
     */
    public function getClientX509CertUrl(): string
    {
        return (string) $this->config['clientX509CertUrl'];
    }
}
