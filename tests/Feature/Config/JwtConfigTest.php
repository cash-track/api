<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\JwtConfig;
use Tests\Fixtures;
use Tests\TestCase;

class JwtConfigTest extends TestCase
{
    public function testConfigs(): void
    {
        $config = $this->getContainer()->get(JwtConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'secret'           => Fixtures::string(),
            'ttl'              => Fixtures::integer(1, 3600),
            'refreshTtl'       => Fixtures::integer(1, 3600),
            'publicKey'        => base64_encode(Fixtures::string()),
            'privateKey'       => base64_encode(Fixtures::string()),
            'issuer'           => Fixtures::url(),
            'audience'         => Fixtures::url(),
            'accessPublicKey'  => base64_encode(Fixtures::string()),
            'accessPrivateKey' => base64_encode(Fixtures::string()),
        ]);

        $this->assertNotEmpty($config->getSecret());
        $this->assertGreaterThan(0, $config->getTtl());
        $this->assertGreaterThan(0, $config->getRefreshTtl());
        $this->assertNotEmpty($config->getPublicKey());
        $this->assertNotEmpty($config->getPrivateKey());
        $this->assertNotEmpty($config->getIssuer());
        $this->assertNotEmpty($config->getAudience());
        $this->assertNotEmpty($config->getAccessPublicKey());
        $this->assertNotEmpty($config->getAccessPrivateKey());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $config->getAccessKeyId());
    }

    public function testGetAccessKeyIdIsEmptyWhenNoAccessPublicKeyIsConfigured(): void
    {
        $config = $this->getContainer()->get(JwtConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'accessPublicKey' => null,
        ]);

        $this->assertSame('', $config->getAccessKeyId());
    }
}
