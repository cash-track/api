<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

use Psr\Log\LoggerInterface;
use Redis;

final class RedisRateLimit implements RateLimitInterface
{
    const string PREFIX = 'rate-limit:';

    public function __construct(
        protected readonly Redis $redis,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function hit(RuleInterface $rule): RateLimitHitInterface
    {
        if (! $this->redis->isConnected()) {
            return new RateLimitHit($rule);
        }

        $key = static::PREFIX . $rule->key();

        // isConnected() can be stale, so each command is guarded to fail open on a mid-outage
        // drop. A false return (not a throw) means a reachable server replied badly — still throws.
        try {
            $counter = $this->redis->incr($key);
        } catch (\Throwable $exception) {
            return $this->unavailable($rule, $exception);
        }

        if (! is_int($counter)) {
            throw new \RuntimeException(
                "Unable to increment rate limit counter: {$this->redis->getLastError()}"
            );
        }

        try {
            $ttl = $this->redis->ttl($key);
        } catch (\Throwable $exception) {
            return $this->unavailable($rule, $exception);
        }

        if (! is_int($ttl)) {
            throw new \RuntimeException(
                "Unable to retrieve rate limit counter time to live: {$this->redis->getLastError()}"
            );
        }

        if ($ttl === -1) {
            try {
                $expired = $this->redis->expire($key, $rule->ttl());
            } catch (\Throwable $exception) {
                return $this->unavailable($rule, $exception);
            }

            if ($expired === false) {
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

    /** Fails open: brute-force protection is off for the outage, so the gap must be logged. */
    private function unavailable(RuleInterface $rule, \Throwable $exception): RateLimitHitInterface
    {
        $this->logger->error('Rate limiting is unavailable; failing open', [
            'error' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);

        return new RateLimitHit($rule);
    }
}
