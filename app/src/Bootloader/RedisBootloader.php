<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\RedisConfig;
use App\Redis\ReconnectingRedis;
use Psr\Log\LoggerInterface;
use Redis;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

final class RedisBootloader extends Bootloader
{
    public function __construct(private readonly RedisConfig $config, private readonly LoggerInterface $logger)
    {
    }

    /** Singleton for the worker's lifetime; ReconnectingRedis keeps it usable after a failure. */
    public function boot(Container $container): void
    {
        $container->bindSingleton(Redis::class, fn (): Redis => new ReconnectingRedis($this->config, $this->logger));
    }
}
