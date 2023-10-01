<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\GuestRule;
use App\Service\RateLimit\RedisRateLimit;
use Tests\TestCase;

class RedisRateLimitTest extends TestCase
{
    public function testCounterException()
    {
        $redis = $this->getMockBuilder(\Redis::class)->onlyMethods(['isConnected', 'incr', 'getLastError'])->getMock();
        $redis->method('isConnected')->willReturn(true);
        $redis->method('incr')->willReturn(false);
        $redis->method('getLastError')->willReturn('unknown error');

        $rateLimit = new RedisRateLimit($redis);

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

        $rateLimit = new RedisRateLimit($redis);

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

        $rateLimit = new RedisRateLimit($redis);

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

        $rateLimit = new RedisRateLimit($redis);

        $this->expectException(\RuntimeException::class);

        $rateLimit->hit(new GuestRule());
    }
}
