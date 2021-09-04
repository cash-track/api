<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\FirebaseConfig;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

class FirebaseBootloader extends Bootloader
{
    /**
     * @var \App\Config\FirebaseConfig
     */
    private $config;

    /**
     * FirebaseBootloader constructor.
     *
     * @param \App\Config\FirebaseConfig $config
     */
    public function __construct(FirebaseConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Spiral\Core\Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
        $container->bind(Factory::class, function (): Factory {
            $factory = (new Factory())
                ->withDatabaseUri($this->config->getDatabaseUri())
                ->withDefaultStorageBucket($this->config->getStorageBucket())
                ->withDefaultStorageBucket($this->config->getStorageBucket())
                ->withServiceAccount($this->getServiceAccount());

            return $factory;
        });
    }

    /**
     * @return array
     */
    public function getServiceAccount(): array
    {
        return [
            'type'                        => 'service_account',
            'project_id'                  => $this->config->getProjectId(),
            'private_key_id'              => $this->config->getPrivateKeyId(),
            'private_key'                 => $this->config->getPrivateKey(),
            'client_email'                => $this->config->getClientEmail(),
            'client_id'                   => $this->config->getClientId(),
            'auth_uri'                    => $this->config->getAuthUri(),
            'token_uri'                   => $this->config->getTokenUri(),
            'auth_provider_x509_cert_url' => $this->config->getAuthProviderX509CertUrl(),
            'client_x509_cert_url'        => $this->config->getClientX509CertUrl(),
        ];
    }
}
