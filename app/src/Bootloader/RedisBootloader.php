<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\RedisConfig;
use Psr\Log\LoggerInterface;
use Redis;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

class RedisBootloader extends Bootloader
{
    public function __construct(private readonly RedisConfig $config, private readonly LoggerInterface $logger)
    {
    }

    public function boot(Container $container): void
    {
        $container->bindSingleton(Redis::class, fn (): Redis => $this->resolve());
    }

    protected function resolve(): Redis
    {
        $redis = new Redis();

        if ($this->config->getHost() === '') {
            return $redis;
        }

        $uri = "{$this->config->getHost()}:{$this->config->getPort()}";

        $this->logger->info("Resolving a connection to a Redis instance [{$uri}]");

        $status = $redis->pconnect(
            $this->config->getHost(),
            $this->config->getPort(),
            $this->config->getTimeout(),
            null,
            $this->config->getRetryInterval(),
            $this->config->getRetryTimeout(),
        );

        if (! $status) {
            $this->logger->emergency("Connection to a Redis instance failed [{$uri}]: {$redis->getLastError()}");

            throw new \RedisException("Unable to connect to Redis");
        }

        if ($redis->ping() === false) {
            $this->logger->emergency("PING to a Redis instance is not successful [{$uri}]: {$redis->getLastError()}");

            throw new \RedisException("Unable to connect to Redis");
        }

        $redis->setOption(Redis::OPT_PREFIX, $this->config->getPrefix());
        $redis->setOption(Redis::OPT_MAX_RETRIES, $this->config->getMaxRetries());
        $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

        $this->logger->info("Connection to a Redis instance has been established [{$uri}]");

        return $redis;
    }
}
