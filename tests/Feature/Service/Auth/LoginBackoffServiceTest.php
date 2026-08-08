<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Auth;

use App\Service\Auth\LoginBackoffService;
use App\Service\Auth\LoginThrottledException;
use Psr\Log\LoggerInterface;
use Tests\Fixtures;
use Tests\TestCase;

class LoginBackoffServiceTest extends TestCase
{
    public function testNotBlockedWhenNoDeadlineIsStored(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'hGet'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        // No `until` field yet (e.g. brand new email, or one still within FREE_ATTEMPTS).
        $redis->method('hGet')->willReturn(false);

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->assertNotThrottled(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    public function testNotBlockedWhenStoredDeadlineHasAlreadyPassed(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'hGet'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hGet')->willReturn((string) (time() - 5));

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        // Once `until` is in the past, the request goes through regardless of how many failures
        // preceded it — the account is never permanently locked.
        $service->assertNotThrottled(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    /** @dataProvider secondsAheadProvider */
    public function testThrowsWithRemainingSecondsWhenStoredDeadlineIsInTheFuture(int $secondsAhead): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'hGet'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hGet')->willReturn((string) (time() + $secondsAhead));

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        try {
            $service->assertNotThrottled(Fixtures::email());

            $this->fail('Expected LoginThrottledException was not thrown.');
        } catch (LoginThrottledException $exception) {
            // +/-1s tolerance for the wall-clock tick between building the fixture and the
            // assertion inside the service.
            $this->assertGreaterThanOrEqual($secondsAhead - 1, $exception->getRetryAfter());
            $this->assertLessThanOrEqual($secondsAhead, $exception->getRetryAfter());
        }
    }

    public function secondsAheadProvider(): array
    {
        return [
            'just throttled' => [2],
            'mid backoff' => [8],
            'capped' => [60],
        ];
    }

    /** @dataProvider freeAttemptsProvider */
    public function testRecordFailureDoesNotSetADeadlineForFreeAttempts(int $attemptsAfterIncrement): void
    {
        $redis = $this->getMockBuilder(\Redis::class)
                       ->onlyMethods(['isConnected', 'hIncrBy', 'hSet', 'ttl'])
                       ->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hIncrBy')->willReturn($attemptsAfterIncrement);
        $redis->method('ttl')->willReturn(500);
        $redis->expects($this->never())->method('hSet');

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordFailure(Fixtures::email());
    }

    public function freeAttemptsProvider(): array
    {
        return [
            '1st failure' => [1],
            '2nd failure' => [2],
        ];
    }

    /** @dataProvider delayProvider */
    public function testRecordFailureSetsADeadlineWithTheCorrectDelayOnceFreeAttemptsAreUsedUp(
        int $attemptsAfterIncrement,
        int $expectedDelay,
    ): void {
        $redis = $this->getMockBuilder(\Redis::class)
                       ->onlyMethods(['isConnected', 'hIncrBy', 'hSet', 'ttl'])
                       ->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hIncrBy')->willReturn($attemptsAfterIncrement);
        $redis->method('ttl')->willReturn(500);

        $before = time();
        $redis->expects($this->once())
              ->method('hSet')
              ->with($this->anything(), 'until', $this->callback(function (string $value) use ($expectedDelay, $before) {
                  $until = (int) $value;

                  $this->assertGreaterThanOrEqual($before + $expectedDelay, $until);
                  $this->assertLessThanOrEqual($before + $expectedDelay + 1, $until);

                  return true;
              }))
              ->willReturn(1);

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordFailure(Fixtures::email());
    }

    public function delayProvider(): array
    {
        return [
            'first delayed attempt (3rd failure)' => [3, 2],
            '4th failure' => [4, 4],
            '5th failure' => [5, 8],
            '6th failure' => [6, 16],
            '7th failure' => [7, 32],
            '8th failure hits the cap' => [8, 60],
            'far beyond the cap stays capped' => [50, 60],
        ];
    }

    public function testRecordFailureIncrementsCounterAndSetsTtlOnlyOnFirstIncrement(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)
                       ->onlyMethods(['isConnected', 'hIncrBy', 'ttl', 'expire'])
                       ->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())->method('hIncrBy')->willReturn(1);
        $redis->expects($this->once())->method('ttl')->willReturn(-1);
        $redis->expects($this->once())->method('expire')->with($this->anything(), 900)->willReturn(true);

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordFailure(Fixtures::email());
    }

    public function testRecordFailureDoesNotReExpireAnAlreadyTtlBackedCounter(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)
                       ->onlyMethods(['isConnected', 'hIncrBy', 'ttl', 'expire'])
                       ->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())->method('hIncrBy')->willReturn(4);
        $redis->expects($this->once())->method('ttl')->willReturn(500);
        $redis->expects($this->never())->method('expire');

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordFailure(Fixtures::email());
    }

    public function testRecordSuccessDeletesTheCounter(): void
    {
        $email = Fixtures::email();

        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'del'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->once())
              ->method('del')
              ->with('login-backoff:' . hash('sha256', $email))
              ->willReturn(1);

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordSuccess($email);
    }

    public function testFailsOpenAndSkipsRedisCallsWhenNotConnected(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)
                       ->onlyMethods(['isConnected', 'hGet', 'hIncrBy', 'del'])
                       ->getMock();
        $redis->method('isConnected')->willReturn(false);
        $redis->expects($this->never())->method('hGet');
        $redis->expects($this->never())->method('hIncrBy');
        $redis->expects($this->never())->method('del');

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->assertNotThrottled(Fixtures::email());
        $service->recordFailure(Fixtures::email());
        $service->recordSuccess(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    public function testFailsOpenAndLogsWhenHGetThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'hGet'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hGet')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())
               ->method('error')
               ->with(
                   'Login backoff storage is unavailable; failing open',
                   $this->callback(function (array $context) {
                       $this->assertEquals(\RedisException::class, $context['error']);
                       $this->assertEquals('Connection lost', $context['message']);

                       return true;
                   }),
               );

        $service = new LoginBackoffService($redis, $logger);

        $service->assertNotThrottled(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    public function testRecordFailureFailsOpenAndLogsWhenHIncrByThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'hIncrBy'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('hIncrBy')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('error');

        $service = new LoginBackoffService($redis, $logger);

        $service->recordFailure(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    public function testRecordSuccessFailsOpenAndLogsWhenDelThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'del'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('del')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('error');

        $service = new LoginBackoffService($redis, $logger);

        $service->recordSuccess(Fixtures::email());

        $this->addToAssertionCount(1);
    }

    public function testDifferentEmailsUseIndependentCounterKeys(): void
    {
        $emailA = Fixtures::email();
        $emailB = Fixtures::email();

        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'del'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->expects($this->exactly(2))
              ->method('del')
              ->with($this->callback(function (string $key) use ($emailA, $emailB) {
                  static $seen = [];
                  $seen[] = $key;

                  $this->assertContains($key, [
                      'login-backoff:' . hash('sha256', $emailA),
                      'login-backoff:' . hash('sha256', $emailB),
                  ]);

                  return true;
              }))
              ->willReturn(1);

        $service = new LoginBackoffService($redis, $this->getContainer()->get(LoggerInterface::class));

        $service->recordSuccess($emailA);
        $service->recordSuccess($emailB);
    }
}
