<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Auth;

use App\Config\JwtConfig;
use App\Service\Auth\JwksService;
use Tests\TestCase;
use Tests\Traits\ProvideRsaKeyPair;

class JwksServiceTest extends TestCase
{
    use ProvideRsaKeyPair;

    public function testEmptyKeySetWhenAccessKeypairNotConfigured(): void
    {
        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getAccessPublicKey')->willReturn('');

        $service = new JwksService($config);

        $this->assertEquals(['keys' => []], $service->getKeySet());
    }

    public function testReturnsRfc7517KeyWhenAccessKeypairConfigured(): void
    {
        $keyPair = $this->generateRsaKeyPair();

        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getAccessPublicKey')->willReturn($keyPair['publicKey']);
        $config->method('getAccessKeyId')->willReturn('test-kid');

        $service = new JwksService($config);
        $keySet = $service->getKeySet();

        $this->assertCount(1, $keySet['keys']);

        $key = $keySet['keys'][0];

        $this->assertEquals('RSA', $key['kty']);
        $this->assertEquals('sig', $key['use']);
        $this->assertEquals('RS256', $key['alg']);
        $this->assertEquals('test-kid', $key['kid']);
        $this->assertNotEmpty($key['n']);
        $this->assertNotEmpty($key['e']);
        $this->assertStringNotContainsString('+', $key['n'], 'n must be base64url, not base64');
        $this->assertStringNotContainsString('=', $key['n'], 'n must be unpadded base64url');
    }

    public function testKeySetNeverExposesHmacSecret(): void
    {
        $config = $this->getMockBuilder(JwtConfig::class)->getMock();
        $config->method('getAccessPublicKey')->willReturn('');
        $config->method('getSecret')->willReturn('super-secret-hmac-key');

        $service = new JwksService($config);

        $this->assertEquals(['keys' => []], $service->getKeySet());
    }
}
