<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\FirebaseConfig;
use Kreait\Firebase\Factory;
use Nyholm\Psr7\Uri;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

final class FirebaseBootloader extends Bootloader
{
    public function __construct(private readonly FirebaseConfig $config)
    {
    }

    public function boot(Container $container): void
    {
        $container->bind(Factory::class, function (): Factory {
            $factory = (new Factory())
                ->withDatabaseUri(new Uri($this->config->getDatabaseUri()))
                ->withServiceAccount($this->getServiceAccount());

            if (($storageBucket = $this->config->getStorageBucket()) !== '') {
                $factory = $factory
                    ->withDefaultStorageBucket($storageBucket)
                    ->withDefaultStorageBucket($storageBucket);
            }

            return $factory;
        });
    }

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
