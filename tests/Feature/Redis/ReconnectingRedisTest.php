<?php

declare(strict_types=1);

namespace Tests\Feature\Redis;

use App\Config\RedisConfig;
use App\Redis\ReconnectingRedis;
use App\Redis\RedisUnavailableException;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Tests\TestCase;

/**
 * ReconnectingRedis is the state machine that lets the shared Redis::class singleton recover
 * from a transient outage without a worker restart (see RedisBootloader). These tests exercise
 * it directly rather than end-to-end, since Spiral\Testing\TestCase builds a fresh container
 * per test method.
 */
class ReconnectingRedisTest extends TestCase
{
    // Nothing listens on port 1, so the OS refuses immediately instead of timing out.
    private const string UNREACHABLE_CONNECTION = '127.0.0.1:1';

    public function testNeverThrowsAndReportsDisconnectedWhenTargetIsUnreachable(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->atLeastOnce())->method('error');

        $redis = new ReconnectingRedis($this->config(self::UNREACHABLE_CONNECTION), $logger);

        $this->assertFalse($redis->isConnected());
    }

    public function testDoesNotRetryOrLogAgainWithinTheCooldownWindow(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        // The constructor makes the first attempt; two more calls within the cooldown must not
        // trigger a second attempt or log.
        $logger->expects($this->once())->method('error');

        $redis = new ReconnectingRedis($this->config(self::UNREACHABLE_CONNECTION), $logger);

        $this->assertFalse($redis->isConnected());
        $this->assertFalse($redis->isConnected());
    }

    public function testLogsErrorOnceThenWarningsDuringTheSameOutage(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        // First failure = error with exception (Sentry). Later retries = warning, no exception key.
        $logger->expects($this->once())->method('error')->with(
            'Connection to a Redis instance failed',
            $this->callback(static fn (array $context): bool => $context['exception'] instanceof RedisUnavailableException),
        );
        $logger->expects($this->exactly(2))->method('warning')->with(
            'Redis instance is still unreachable',
            $this->callback(static fn (array $context): bool => ! array_key_exists('exception', $context)
                && is_string($context['error'])),
        );

        $redis = new ReconnectingRedis($this->config(self::UNREACHABLE_CONNECTION), $logger);

        $this->forceReconnectCooldownToHaveElapsed($redis);
        $this->assertFalse($redis->isConnected());

        $this->forceReconnectCooldownToHaveElapsed($redis);
        $this->assertFalse($redis->isConnected());
    }

    public function testLogsRecoveryOnceAtInfo(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->never())->method('error');
        $messages = [];
        $logger->method('info')->willReturnCallback(static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $redis = new ReconnectingRedis($this->config('localhost:6379'), $logger);
        $redis->close();
        (new ReflectionProperty(ReconnectingRedis::class, 'down'))->setValue($redis, true);
        $this->forceReconnectCooldownToHaveElapsed($redis);

        $this->assertTrue($redis->isConnected());
        $this->assertTrue($redis->isConnected());

        $this->assertSame([
            'Connection to a Redis instance has been established [localhost:6379]',
            'Connection to a Redis instance has been restored [localhost:6379]',
        ], $messages);
    }

    public function testConnectsSuccessfullyAndStopsAttemptingOnceTargetIsReachable(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->never())->method('error');

        // Points at the real test Redis, proving a genuine connect/ping round trip, not a
        // mocked-away "success."
        $redis = new ReconnectingRedis($this->config('localhost:6379'), $logger);

        $this->assertTrue($redis->isConnected());
        // A second call must reuse the now-live connection, not attempt again.
        $this->assertTrue($redis->isConnected());
    }

    public function testRedisSingletonResolvedFromContainerIsTheSameInstanceAcrossMultipleGets(): void
    {
        // Confirms the premise the fix depends on: Redis::class is cached, not rebuilt, across
        // repeated resolutions from one container.
        $first = $this->getContainer()->get(\Redis::class);
        $second = $this->getContainer()->get(\Redis::class);

        $this->assertSame($first, $second);
    }

    private function config(string $connection): RedisConfig
    {
        return new RedisConfig([
            'connection' => $connection,
            'timeout' => 1.0,
            'retry_interval' => 2,
            'retry_timeout' => 1.0,
            'prefix' => 'CT:testing:reconnect:',
            'max_retries' => 1,
        ]);
    }

    private function forceReconnectCooldownToHaveElapsed(ReconnectingRedis $redis): void
    {
        $property = new ReflectionProperty(ReconnectingRedis::class, 'lastAttemptAt');
        $property->setAccessible(true);
        $property->setValue($redis, microtime(true) - 3600);
    }
}
