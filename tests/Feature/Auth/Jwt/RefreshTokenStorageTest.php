<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Jwt;

use App\Auth\Jwt\RefreshTokenStorage;
use App\Config\JwtConfig;
use Firebase\JWT\JWT;
use PHPUnit\Framework\MockObject\MockObject;
use Redis;
use Spiral\Auth\Exception\TokenStorageException;
use Tests\TestCase;
use Tests\Traits\ProvideRsaKeyPair;

class RefreshTokenStorageTest extends TestCase
{
    use ProvideRsaKeyPair;

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeConfig(array $overrides = []): JwtConfig&MockObject
    {
        $values = array_merge([
            'getRefreshTtl' => 3600,
            'getPublicKey' => '123',
            'getPrivateKey' => '123',
            'getIssuer' => 'https://api.cash-track.app',
            'getAudience' => 'https://api.cash-track.app',
        ], $overrides);

        $config = $this->getMockBuilder(JwtConfig::class)->getMock();

        foreach ($values as $method => $value) {
            $config->method($method)->willReturn($value);
        }

        return $config;
    }

    private function disconnectedRedis(): Redis&MockObject
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected'])->getMock();
        $redis->method('isConnected')->willReturn(false);

        return $redis;
    }

    public function testEmptyPublicKeyThrownException(): void
    {
        $config = $this->makeConfig(['getPublicKey' => '']);

        $this->expectException(TokenStorageException::class);

        new RefreshTokenStorage($config, $this->disconnectedRedis());
    }

    public function testEmptyPrivateKeyThrownException(): void
    {
        $config = $this->makeConfig(['getPrivateKey' => '']);

        $this->expectException(TokenStorageException::class);

        new RefreshTokenStorage($config, $this->disconnectedRedis());
    }

    public function testRoundTripUsesRs256WithDedicatedKeypair(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getPublicKey' => $keyPair['publicKey'],
            'getPrivateKey' => $keyPair['privateKey'],
        ]);

        $storage = new RefreshTokenStorage($config, $this->disconnectedRedis());

        $token = $storage->create(['sub' => 7, 'kind' => 'refresh']);
        $loaded = $storage->load($token->getID());

        $this->assertNotNull($loaded);
        $this->assertEquals(7, $loaded->getPayload()['sub']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token->getPayload()['jti']);
    }

    public function testHs256TokenIsRejectedEvenIfSignedWithPublicKeyAsSecret(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getPublicKey' => $keyPair['publicKey'],
            'getPrivateKey' => $keyPair['privateKey'],
        ]);

        $storage = new RefreshTokenStorage($config, $this->disconnectedRedis());

        $forgedToken = JWT::encode([
            'sub' => 1,
            'jti' => bin2hex(random_bytes(16)),
        ], $keyPair['publicKey'], 'HS256');

        $this->assertNull($storage->load($forgedToken));
    }

    public function testDeleteBlacklistsTokenWithRemainingTtl(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getPublicKey' => $keyPair['publicKey'],
            'getPrivateKey' => $keyPair['privateKey'],
        ]);

        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'setex'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())->method('setex')->with(
            $this->stringStartsWith('blacklist:jti:'),
            $this->greaterThan(0),
            '1',
        );

        $storage = new RefreshTokenStorage($config, $redis);

        $storage->delete($storage->create(['sub' => 1, 'kind' => 'refresh']));
    }
}
