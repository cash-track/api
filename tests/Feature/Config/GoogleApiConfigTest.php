<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\GoogleApiConfig;
use Tests\Fixtures;
use Tests\TestCase;

class GoogleApiConfigTest extends TestCase
{
    public function testConfigs(): void
    {
        /** @var \App\Config\GoogleApiConfig $config */
        $config = $this->getContainer()->get(GoogleApiConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'clientId'                => Fixtures::string(),
            'clientSecret'            => Fixtures::string(),
            'projectId'               => Fixtures::string(),
            'authUri'                 => Fixtures::url(),
            'tokenUri'                => Fixtures::url(),
            'authProviderX509CertUrl' => Fixtures::url(),
            'redirectUris'            => [Fixtures::url()],
        ]);

        $this->assertNotEmpty($config->getClientId());
        $this->assertNotEmpty($config->getClientSecret());
        $this->assertNotEmpty($config->getProjectId());
        $this->assertNotEmpty($config->getAuthUri());
        $this->assertNotEmpty($config->getTokenUri());
        $this->assertNotEmpty($config->getAuthProviderX509CertUrl());
        $this->assertNotEmpty($config->getRedirectUris());
    }
}
