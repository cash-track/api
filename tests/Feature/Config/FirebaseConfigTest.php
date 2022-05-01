<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\FirebaseConfig;
use Tests\Fixtures;
use Tests\TestCase;

class FirebaseConfigTest extends TestCase
{
    public function testConfigs(): void
    {
        $config = $this->getContainer()->get(FirebaseConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'databaseUri'             => Fixtures::url(),
            'storageBucket'           => Fixtures::string(),
            'projectId'               => Fixtures::string(),
            'privateKeyId'            => Fixtures::string(),
            'privateKey'              => Fixtures::string(),
            'clientEmail'             => Fixtures::email(),
            'clientId'                => Fixtures::string(),
            'authUri'                 => Fixtures::url(),
            'tokenUri'                => Fixtures::url(),
            'authProviderX509CertUrl' => Fixtures::url(),
            'clientX509CertUrl'       => Fixtures::url(),
        ]);

        $this->assertNotEmpty($config->getDatabaseUri());
        $this->assertNotEmpty($config->getStorageBucket());
        $this->assertNotEmpty($config->getProjectId());
        $this->assertNotEmpty($config->getPrivateKeyId());
        $this->assertNotEmpty($config->getPrivateKey());
        $this->assertNotEmpty($config->getClientEmail());
        $this->assertNotEmpty($config->getClientId());
        $this->assertNotEmpty($config->getAuthUri());
        $this->assertNotEmpty($config->getTokenUri());
        $this->assertNotEmpty($config->getAuthProviderX509CertUrl());
        $this->assertNotEmpty($config->getClientX509CertUrl());
    }
}
