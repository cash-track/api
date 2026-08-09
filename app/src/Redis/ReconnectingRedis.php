<?php

declare(strict_types=1);

namespace App\Redis;

use App\Config\RedisConfig;
use Psr\Log\LoggerInterface;
use Redis;
use RedisException;

/**
 * A \Redis client that never throws on connect and retries on a cooldown, so a worker recovers
 * from a Redis outage without a restart. Consumers drive the retry via isConnected().
 */
final class ReconnectingRedis extends Redis
{
    /** Caps a sustained outage at one connect timeout per window instead of one per request. */
    private const int RECONNECT_COOLDOWN_SECONDS = 5;

    private ?float $lastAttemptAt = null;

    public function __construct(
        private readonly RedisConfig $config,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();

        // Eager connect for consumers that skip their own isConnected() check. Never throws.
        $this->isConnected();
    }

    #[\Override]
    public function isConnected(): bool
    {
        if (parent::isConnected()) {
            return true;
        }

        if (! $this->shouldAttemptConnect()) {
            return false;
        }

        return $this->attemptConnect();
    }

    private function shouldAttemptConnect(): bool
    {
        if ($this->config->getHost() === '') {
            return false;
        }

        if ($this->lastAttemptAt === null) {
            return true;
        }

        return microtime(true) - $this->lastAttemptAt >= self::RECONNECT_COOLDOWN_SECONDS;
    }

    private function attemptConnect(): bool
    {
        $this->lastAttemptAt = microtime(true);

        $uri = "{$this->config->getHost()}:{$this->config->getPort()}";

        try {
            $status = $this->pconnect(
                $this->config->getHost(),
                $this->config->getPort(),
                $this->config->getTimeout(),
                null,
                $this->config->getRetryInterval(),
                $this->config->getRetryTimeout(),
            );

            if (! $status || $this->ping() === false) {
                throw new RedisException("Unable to connect to Redis: {$this->getLastError()}");
            }
        } catch (RedisException $exception) {
            // Repeats every cooldown window: the only signal revocation and rate limiting are off.
            $this->logger->emergency("Connection to a Redis instance failed [{$uri}]: {$exception->getMessage()}");

            return false;
        }

        $this->setOption(Redis::OPT_PREFIX, $this->config->getPrefix());
        $this->setOption(Redis::OPT_MAX_RETRIES, $this->config->getMaxRetries());
        $this->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

        $this->logger->info("Connection to a Redis instance has been established [{$uri}]");

        return true;
    }
}
