<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\GuestRule;
use App\Service\RateLimit\RedisRateLimit;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class RedisRateLimitTest extends TestCase
{
    public function testCounterException()
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'getLastError'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(false);
        $redis->method('getLastError')->willReturn('unknown error');

        $rateLimit = new RedisRateLimit($redis, $this->getContainer()->get(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);

        $rateLimit->hit(new GuestRule());
    }

    public function testTtlException()
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'getLastError', 'ttl'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(1);
        $redis->method('getLastError')->willReturn('unknown error');
        $redis->method('ttl')->willReturn(-1);

        $rateLimit = new RedisRateLimit($redis, $this->getContainer()->get(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);

        $rateLimit->hit(new GuestRule());
    }

    public function testTtlMissing()
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'getLastError', 'ttl'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(1);
        $redis->method('getLastError')->willReturn('unknown error');
        $redis->method('ttl')->willReturn(false);

        $rateLimit = new RedisRateLimit($redis, $this->getContainer()->get(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);

        $rateLimit->hit(new GuestRule());
    }

    public function testExpireException()
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'getLastError', 'ttl', 'expire'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(1);
        $redis->method('getLastError')->willReturn('unknown error');
        $redis->method('ttl')->willReturn(-1);
        $redis->method('expire')->willReturn(false);

        $rateLimit = new RedisRateLimit($redis, $this->getContainer()->get(LoggerInterface::class));

        $this->expectException(\RuntimeException::class);

        $rateLimit->hit(new GuestRule());
    }

    /**
     * Unlike TestCounterException et al above, these report isConnected() === true but make
     * incr()/ttl()/expire() throw RedisException, proving hit() fails open rather than letting
     * the exception reach RateLimitMiddleware uncaught.
     */
    public function testHitFailsOpenAndLogsWhenIncrThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())
               ->method('error')
               ->with(
                   'Rate limiting is unavailable; failing open',
                   $this->callback(function (array $context) {
                       $this->assertEquals(\RedisException::class, $context['error']);
                       $this->assertEquals('Connection lost', $context['message']);
                       return true;
                   }),
               );

        $rateLimit = new RedisRateLimit($redis, $logger);

        $rule = new GuestRule();
        $hit = $rateLimit->hit($rule);

        $this->assertFalse($hit->isReached());
        $this->assertEquals($rule->limit(), $hit->getRemaining());
    }

    public function testHitFailsOpenAndLogsWhenTtlThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'ttl'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(1);
        $redis->method('ttl')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('error');

        $rateLimit = new RedisRateLimit($redis, $logger);

        $rule = new GuestRule();
        $hit = $rateLimit->hit($rule);

        $this->assertFalse($hit->isReached());
        $this->assertEquals($rule->limit(), $hit->getRemaining());
    }

    public function testHitFailsOpenAndLogsWhenExpireThrows(): void
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'ttl', 'expire'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(1);
        $redis->method('ttl')->willReturn(-1);
        $redis->method('expire')->willThrowException(new \RedisException('Connection lost'));

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())->method('error');

        $rateLimit = new RedisRateLimit($redis, $logger);

        $rule = new GuestRule();
        $hit = $rateLimit->hit($rule);

        $this->assertFalse($hit->isReached());
        $this->assertEquals($rule->limit(), $hit->getRemaining());
    }
}
