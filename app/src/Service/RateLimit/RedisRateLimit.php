<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

use Redis;

class RedisRateLimit implements RateLimitInterface
{
    const PREFIX = 'rate-limit:';

    public function __construct(
        protected readonly Redis $redis,
    ) {
    }

    public function hit(RuleInterface $rule): RateLimitHitInterface
    {
        if (! $this->redis->isConnected()) {
            return new RateLimitHit($rule);
        }

        $key = static::PREFIX . $rule->key();

        $counter = $this->redis->incr($key);
        if (! is_int($counter)) {
            throw new \RuntimeException(
                "Unable to increment rate limit counter: {$this->redis->getLastError()}"
            );
        }

        $ttl = $this->redis->ttl($key);
        if (! is_int($ttl)) {
            throw new \RuntimeException(
                "Unable to retrieve rate limit counter time to live: {$this->redis->getLastError()}"
            );
        }

        if ($ttl === -1) {
            if ($this->redis->expire($key, $rule->ttl()) === false) {
                throw new \RuntimeException(
                    "Unable to set expiration for rate limit counter: {$this->redis->getLastError()}"
                );
            }

            $ttl = $rule->ttl();
        }

        $hit = new RateLimitHit($rule, $counter, $ttl);

        if ($hit->isReached()) {
            throw new RateLimitReachedException($hit);
        }

        return $hit;
    }
}
