<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\FirebaseConfig;
use Tests\TestCase;

class FirebaseConfigTest extends TestCase
{
    public function testGetUsersVerifyType(): void
    {
        $config = $this->getContainer()->get(FirebaseConfig::class);
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
