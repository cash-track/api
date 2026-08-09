<?php

declare(strict_types=1);

namespace App\Service\Auth;

use Psr\Log\LoggerInterface;
use Redis;

/**
 * Per-email failed-login backoff, independent of RateLimitMiddleware — LoginController calls
 * recordFailure()/recordSuccess() around AuthService::login().
 *
 * Keyed by a hash of the email in a Redis hash: `attempts` (count, drives the next delay) and
 * `until` (unix timestamp requests are blocked before). Gating on `until` rather than `attempts`
 * is what makes this a backoff and not a lockout — once it's past, the next request reaches
 * AuthService::login() again, so a correct password ends the streak. First FREE_ATTEMPTS
 * failures don't set `until`; each one after doubles the wait, capped at MAX_DELAY_SEC.
 *
 * Keying by email alone means it can be used to lock out a known address — accepted tradeoff,
 * bounded by the cap.
 */
final class LoginBackoffService
{
    private const string PREFIX = 'login-backoff:';

    /** Failures below this count never set a block deadline. */
    private const int FREE_ATTEMPTS = 3;

    private const int BASE_DELAY_SEC = 2;

    /** Ceiling on the backoff — a slowdown, not an outage. */
    private const int MAX_DELAY_SEC = 60;

    /** How long a failure streak is remembered before it decays back to zero. */
    private const int WINDOW_TTL_SEC = 900;

    public function __construct(
        private readonly Redis $redis,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws LoginThrottledException while now() is still before the stored block deadline.
     */
    public function assertNotThrottled(string $email): void
    {
        $remaining = $this->getBlockedUntil($email) - time();

        if ($remaining > 0) {
            throw new LoginThrottledException($remaining);
        }
    }

    public function recordFailure(string $email): void
    {
        if (! $this->redis->isConnected()) {
            return;
        }

        $key = $this->key($email);

        try {
            $attempts = $this->redis->hIncrBy($key, 'attempts', 1);

            if (is_int($attempts)) {
                $delay = self::delayFor($attempts);

                if ($delay > 0) {
                    $this->redis->hSet($key, 'until', (string) (time() + $delay));
                }
            }

            if ($this->redis->ttl($key) === -1) {
                $this->redis->expire($key, self::WINDOW_TTL_SEC);
            }
        } catch (\Throwable $exception) {
            $this->logUnavailable($exception);
        }
    }

    public function recordSuccess(string $email): void
    {
        if (! $this->redis->isConnected()) {
            return;
        }

        try {
            $this->redis->del($this->key($email));
        } catch (\Throwable $exception) {
            $this->logUnavailable($exception);
        }
    }

    /** Fails open (never blocked) on any Redis outage — brute-force backoff is off, so log it. */
    private function getBlockedUntil(string $email): int
    {
        if (! $this->redis->isConnected()) {
            return 0;
        }

        try {
            $until = $this->redis->hGet($this->key($email), 'until');
        } catch (\Throwable $exception) {
            $this->logUnavailable($exception);

            return 0;
        }

        return is_numeric($until) ? (int) $until : 0;
    }

    private static function delayFor(int $attempts): int
    {
        if ($attempts < self::FREE_ATTEMPTS) {
            return 0;
        }

        // Cap the exponent so 2**n can't overflow to float on a huge attempt count.
        $exponent = min($attempts - self::FREE_ATTEMPTS, 10);
        $delay = self::BASE_DELAY_SEC * (int) (2 ** $exponent);

        return min($delay, self::MAX_DELAY_SEC);
    }

    private function key(string $email): string
    {
        return self::PREFIX . hash('sha256', $email);
    }

    private function logUnavailable(\Throwable $exception): void
    {
        $this->logger->error('Login backoff storage is unavailable; failing open', [
            'error' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }
}
