<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Idempotency;

use App\Service\Idempotency\IdempotencyRecord;
use App\Service\Idempotency\RedisIdempotencyStore;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Redis;
use Tests\TestCase;

/**
 * Drives the real store against a mocked \Redis, following RedisRateLimitTest's style. The
 * middleware tests mock the store interface and so never exercise the command sequence itself.
 */
class RedisIdempotencyStoreTest extends TestCase
{
    private const string KEY = 'idempotency:42:POST:/v1/wallets/1/charges:1e6c2a6a-9e2e-4d3a-8f2e-6a2f1b8c9d10';

    public function testSetNxWinsGrantsTheClaim(): void
    {
        $redis = $this->redisMock(['isConnected', 'set']);
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())->method('set')->willReturn(true);

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertTrue($claim->claimed);
        $this->assertTrue($claim->available);
        $this->assertNull($claim->existing);
    }

    public function testSetNxFailsAndGetReturnsACompletedRecordYieldsConflictCarryingIt(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(true);
        $redis->method('set')->willReturn(false);
        $redis->method('get')->willReturn($this->encodeCompleted('fp', 200, '{"data":1}', ['X-Foo' => ['bar']]));

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());

        $claim = $store->claim(self::KEY, 'other-fp');

        $this->assertFalse($claim->claimed);
        $this->assertTrue($claim->available);
        $this->assertNotNull($claim->existing);
        $this->assertTrue($claim->existing->isComplete());
        $this->assertSame('fp', $claim->existing->fingerprint);
        $this->assertSame(200, $claim->existing->status);
        $this->assertSame('{"data":1}', $claim->existing->body);
        $this->assertSame(['X-Foo' => ['bar']], $claim->existing->headers);
    }

    public function testSetNxFailsAndGetReturnsAnInFlightRecordYieldsConflict(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(true);
        $redis->method('set')->willReturn(false);
        $redis->method('get')->willReturn($this->encodeInFlight('fp'));

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());

        $claim = $store->claim(self::KEY, 'other-fp');

        $this->assertFalse($claim->claimed);
        $this->assertTrue($claim->available);
        $this->assertNotNull($claim->existing);
        $this->assertFalse($claim->existing->isComplete());
        $this->assertSame('fp', $claim->existing->fingerprint);
    }

    /**
     * A failed SET NX followed by an empty GET (the key vanished between the two) must re-attempt
     * the SET NX — claimed() may only follow a SET NX this request actually won.
     */
    public function testVanishedKeyBetweenSetNxAndGetReAttemptsTheAtomicSetInsteadOfGrantingOwnership(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->exactly(2))->method('set')->willReturnOnConsecutiveCalls(false, true);
        $redis->expects($this->once())->method('get')->willReturn('');

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertTrue($claim->claimed);
        $this->assertTrue($claim->available);
    }

    public function testRetryLoopExhaustionFailsOpen(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->exactly(RedisIdempotencyStore::CLAIM_ATTEMPTS))->method('set')->willReturn(false);
        $redis->expects($this->exactly(RedisIdempotencyStore::CLAIM_ATTEMPTS))->method('get')->willReturn('');

        $logger = $this->loggerMock();
        $logger->expects($this->once())->method('warning')->with($this->stringContains('exhausted'));

        $store = new RedisIdempotencyStore($redis, $logger);

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertFalse($claim->claimed);
        $this->assertFalse($claim->available);
    }

    public function testCorruptPayloadFromGetFailsOpen(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(true);
        $redis->method('set')->willReturn(false);
        $redis->method('get')->willReturn('not-valid-json{{{');

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertFalse($claim->claimed);
        $this->assertFalse($claim->available);
        $this->assertNull($claim->existing);
    }

    public function testNotConnectedFailsOpenWithoutIssuingAnyCommand(): void
    {
        $redis = $this->redisMock(['isConnected', 'set', 'get']);
        $redis->method('isConnected')->willReturn(false);
        $redis->expects($this->never())->method('set');
        $redis->expects($this->never())->method('get');

        $logger = $this->loggerMock();
        $logger->expects($this->never())->method('warning');

        $store = new RedisIdempotencyStore($redis, $logger);

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertFalse($claim->claimed);
        $this->assertFalse($claim->available);
    }

    public function testThrowingCommandFailsOpenAndLogsAWarning(): void
    {
        $redis = $this->redisMock(['isConnected', 'set']);
        $redis->method('isConnected')->willReturn(true);
        $redis->method('set')->willThrowException(new \RedisException('connection lost'));

        $logger = $this->loggerMock();
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('claim'),
                $this->callback(function (array $context): bool {
                    $this->assertSame(\RedisException::class, $context['error']);
                    $this->assertSame('connection lost', $context['message']);

                    return true;
                }),
            );

        $store = new RedisIdempotencyStore($redis, $logger);

        $claim = $store->claim(self::KEY, 'fp');

        $this->assertFalse($claim->claimed);
        $this->assertFalse($claim->available);
    }

    public function testClaimWritesTheInFlightMarkerWithTheShortLeaseTtl(): void
    {
        $redis = $this->redisMock(['isConnected', 'set']);
        $redis->method('isConnected')->willReturn(true);

        $capturedOptions = null;
        $redis->expects($this->once())
            ->method('set')
            ->with(
                self::KEY,
                $this->isType('string'),
                $this->callback(function (array $options) use (&$capturedOptions): bool {
                    $capturedOptions = $options;

                    return true;
                }),
            )
            ->willReturn(true);

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());
        $store->claim(self::KEY, 'fp');

        $this->assertContains('nx', $capturedOptions);
        $this->assertSame(RedisIdempotencyStore::LEASE_TTL, $capturedOptions['ex']);
        $this->assertSame(60, $capturedOptions['ex']);
    }

    public function testCompleteWritesTheRecordWithTheLongTtl(): void
    {
        $redis = $this->redisMock(['isConnected', 'set']);
        $redis->method('isConnected')->willReturn(true);

        $capturedOptions = null;
        $redis->expects($this->once())
            ->method('set')
            ->with(
                self::KEY,
                $this->isType('string'),
                $this->callback(function (array $options) use (&$capturedOptions): bool {
                    $capturedOptions = $options;

                    return true;
                }),
            )
            ->willReturn(true);

        $store = new RedisIdempotencyStore($redis, $this->loggerMock());
        $store->complete(self::KEY, IdempotencyRecord::completed('fp', 200, '{}', []));

        $this->assertNotContains('nx', $capturedOptions);
        $this->assertSame(RedisIdempotencyStore::RECORD_TTL, $capturedOptions['ex']);
        $this->assertSame(86400, $capturedOptions['ex']);
    }

    /**
     * @param list<string> $methods
     * @return Redis&MockObject
     */
    private function redisMock(array $methods): Redis&MockObject
    {
        return $this->getMockBuilder(Redis::class)->onlyMethods($methods)->getMock();
    }

    private function loggerMock(): LoggerInterface&MockObject
    {
        return $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    private function encodeInFlight(string $fingerprint): string
    {
        return (string) json_encode([
            'fingerprint' => $fingerprint,
            'status' => null,
            'body' => null,
            'headers' => null,
        ]);
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function encodeCompleted(string $fingerprint, int $status, string $body, array $headers): string
    {
        return (string) json_encode([
            'fingerprint' => $fingerprint,
            'status' => $status,
            'body' => base64_encode($body),
            'headers' => $headers,
        ]);
    }
}
