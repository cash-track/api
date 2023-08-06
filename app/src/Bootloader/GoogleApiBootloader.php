<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\GoogleApiConfig;
use Google\Client;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

class GoogleApiBootloader extends Bootloader
{
    public function __construct(private readonly GoogleApiConfig $config)
    {
    }

    public function boot(Container $container): void
    {
        $container->bind(Client::class, function (): Client {
            $client = new Client();
            $client->setAuthConfig($this->getAuthConfig());

            return $client;
        });
    }

    public function getAuthConfig(): array
    {
        return [
            'web' => [
                'client_id' => $this->config->getClientId(),
                'project_id' => $this->config->getProjectId(),
                'auth_uri' => $this->config->getAuthUri(),
                'token_uri' => $this->config->getTokenUri(),
                'auth_provider_x509_cert_url' => $this->config->getAuthProviderX509CertUrl(),
                'client_secret' => $this->config->getClientSecret(),
                'redirect_uris' => $this->config->getRedirectUris(),
            ],
        ];
    }
}
