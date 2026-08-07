<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\Jwt;

use App\Auth\Jwt\TokenStorage;
use App\Config\JwtConfig;
use Firebase\JWT\JWT;
use PHPUnit\Framework\MockObject\MockObject;
use Redis;
use Spiral\Auth\Exception\TokenStorageException;
use Tests\TestCase;
use Tests\Traits\ProvideRsaKeyPair;

class TokenStorageTest extends TestCase
{
    use ProvideRsaKeyPair;

    private const HMAC_SECRET = 'test-hmac-secret-at-least-32-bytes-long';

    /**
     * @param array<string, mixed> $overrides
     */
    private function makeConfig(array $overrides = []): JwtConfig&MockObject
    {
        $values = array_merge([
            'getSecret' => self::HMAC_SECRET,
            'getTtl' => 3600,
            'getIssuer' => 'https://api.cash-track.app',
            'getAudience' => 'https://api.cash-track.app',
            'getAccessPublicKey' => '',
            'getAccessPrivateKey' => '',
            'getAccessKeyId' => '',
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

    public function testEmptySecretAndAccessKeypairThrownException(): void
    {
        $config = $this->makeConfig(['getSecret' => '']);

        $this->expectException(TokenStorageException::class);

        new TokenStorage($config, $this->disconnectedRedis());
    }

    public function testConstructsWithSecretOnly(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $this->assertInstanceOf(TokenStorage::class, $storage);
    }

    public function testConstructsWithAccessKeypairEvenWithoutSecret(): void
    {
        $keyPair = $this->generateRsaKeyPair();

        $config = $this->makeConfig([
            'getSecret' => '',
            'getAccessPublicKey' => $keyPair['publicKey'],
            'getAccessPrivateKey' => $keyPair['privateKey'],
            'getAccessKeyId' => 'abc123',
        ]);

        $storage = new TokenStorage($config, $this->disconnectedRedis());

        $this->assertInstanceOf(TokenStorage::class, $storage);
    }

    public function testCreateGeneratesRandomJti(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $tokenOne = $storage->create(['sub' => 1]);
        $tokenTwo = $storage->create(['sub' => 1]);

        $this->assertNotEquals($tokenOne->getPayload()['jti'], $tokenTwo->getPayload()['jti']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $tokenOne->getPayload()['jti']);
    }

    public function testCreateUsesConfiguredIssuerAndAudience(): void
    {
        $config = $this->makeConfig([
            'getIssuer' => 'https://issuer.test',
            'getAudience' => 'https://audience.test',
        ]);
        $storage = new TokenStorage($config, $this->disconnectedRedis());

        $token = $storage->create(['sub' => 1]);

        $this->assertEquals('https://issuer.test', $token->getPayload()['iss']);
        $this->assertEquals('https://audience.test', $token->getPayload()['aud']);
    }

    public function testHs256RoundTrip(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $token = $storage->create(['sub' => 42]);
        $loaded = $storage->load($token->getID());

        $this->assertNotNull($loaded);
        $this->assertEquals(42, $loaded->getPayload()['sub']);
    }

    public function testRs256RoundTripAndKeyIdHeader(): void
    {
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getAccessPublicKey' => $keyPair['publicKey'],
            'getAccessPrivateKey' => $keyPair['privateKey'],
            'getAccessKeyId' => 'test-kid',
        ]);
        $storage = new TokenStorage($config, $this->disconnectedRedis());

        $token = $storage->create(['sub' => 42]);
        $loaded = $storage->load($token->getID());

        $this->assertNotNull($loaded);
        $this->assertEquals(42, $loaded->getPayload()['sub']);

        [$headerB64] = explode('.', $token->getID());
        $header = json_decode(JWT::urlsafeB64Decode($headerB64), true);

        $this->assertEquals('RS256', $header['alg']);
        $this->assertEquals('test-kid', $header['kid']);
    }

    public function testHs256TokenStillVerifiesDuringMigrationWindow(): void
    {
        // Signing always prefers RS256 once configured, so build the legacy HS256 shape
        // directly to simulate a pre-migration token.
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getAccessPublicKey' => $keyPair['publicKey'],
            'getAccessPrivateKey' => $keyPair['privateKey'],
            'getAccessKeyId' => 'test-kid',
        ]);
        $storage = new TokenStorage($config, $this->disconnectedRedis());

        $legacyToken = JWT::encode([
            'sub' => 42,
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => bin2hex(random_bytes(16)),
        ], self::HMAC_SECRET, 'HS256');

        $loaded = $storage->load($legacyToken);

        $this->assertNotNull($loaded);
        $this->assertEquals(42, $loaded->getPayload()['sub']);
    }

    public function testAlgorithmConfusionAttackIsRejected(): void
    {
        // Replays the RS256 public key as an HMAC secret with `alg` flipped to HS256; must
        // fail to verify.
        $keyPair = $this->generateRsaKeyPair();
        $config = $this->makeConfig([
            'getAccessPublicKey' => $keyPair['publicKey'],
            'getAccessPrivateKey' => $keyPair['privateKey'],
            'getAccessKeyId' => 'test-kid',
        ]);
        $storage = new TokenStorage($config, $this->disconnectedRedis());

        $forgedToken = JWT::encode([
            'sub' => 1,
            'jti' => bin2hex(random_bytes(16)),
        ], $keyPair['publicKey'], 'HS256');

        $this->assertNull($storage->load($forgedToken));
    }

    public function testMalformedTokenIsRejected(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $this->assertNull($storage->load('not-a-jwt'));
    }

    public function testBlacklistedTokenIsRejected(): void
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'exists'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('exists')->willReturn(1);

        $storage = new TokenStorage($this->makeConfig(), $redis);

        $token = $storage->create(['sub' => 1]);

        $this->assertNull($storage->load($token->getID()));
    }

    public function testDeleteBlacklistsTokenWithRemainingTtl(): void
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'setex'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())->method('setex')->with(
            $this->stringStartsWith('blacklist:jti:'),
            $this->greaterThan(0),
            '1',
        );

        $storage = new TokenStorage($this->makeConfig(), $redis);

        $storage->delete($storage->create(['sub' => 1]));
    }

    public function testDeleteSkipsAlreadyExpiredToken(): void
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'setex'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->never())->method('setex');

        $storage = new TokenStorage($this->makeConfig(), $redis);

        $token = $storage->create(['sub' => 1], (new \DateTimeImmutable())->sub(new \DateInterval('PT10S')));

        $storage->delete($token);
    }

    public function testLoadFailsOpenWhenRedisUnavailable(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $token = $storage->create(['sub' => 1]);

        $this->assertNotNull($storage->load($token->getID()));
    }

    public function testDeleteIsNoOpWhenRedisUnavailable(): void
    {
        $storage = new TokenStorage($this->makeConfig(), $this->disconnectedRedis());

        $storage->delete($storage->create(['sub' => 1]));

        $this->addToAssertionCount(1);
    }

    /**
     * Covers the try/catch around exists() — a connected client can still drop mid-command,
     * which testLoadFailsOpenWhenRedisUnavailable never reaches.
     */
    public function testLoadFailsOpenWhenRedisThrowsDuringBlacklistCheck(): void
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'exists'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('exists')->willThrowException(new \RedisException('connection lost'));

        $storage = new TokenStorage($this->makeConfig(), $redis);

        $token = $storage->create(['sub' => 1]);

        $this->assertNotNull($storage->load($token->getID()));
    }

    /**
     * Write-side equivalent of testLoadFailsOpenWhenRedisThrowsDuringBlacklistCheck, covering
     * the try/catch around setex().
     */
    public function testDeleteSwallowsExceptionWhenRedisThrowsDuringBlacklistWrite(): void
    {
        $redis = $this->getMockBuilder(Redis::class)->onlyMethods(['isConnected', 'setex'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('setex')->willThrowException(new \RedisException('connection lost'));

        $storage = new TokenStorage($this->makeConfig(), $redis);

        $storage->delete($storage->create(['sub' => 1]));

        $this->addToAssertionCount(1);
    }
}
