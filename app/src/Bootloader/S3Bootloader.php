<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\S3Config;
use App\Service\Idempotency\RedisIdempotencyStore;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

/**
 * Note: It uses ~10MB of memory
 */
final class S3Bootloader extends Bootloader
{
    /** Datacenter-to-datacenter, so failing to even open the connection is fast to detect. */
    const int CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * PUT /profile/photo uploads to S3 synchronously. Half of LEASE_TTL keeps the request well
     * inside its idempotency lease, so a retry can't win a fresh claim and upload concurrently.
     * It also bounds a worker's exposure to a hung external connection — reason enough on its
     * own to keep it.
     */
    const int TIMEOUT_SECONDS = RedisIdempotencyStore::LEASE_TTL / 2;

    public function __construct(
        private readonly S3Config $config,
    ) {
    }

    public function boot(Container $container): void
    {
        $container->bind(S3ClientInterface::class, function (): S3ClientInterface {
            return new S3Client([
                'version' => 'latest',
                'region'  => $this->config->getRegion(),
                'endpoint' => $this->config->getEndpoint(),
                'credentials' => [
                    'key'    => $this->config->getKey(),
                    'secret' => $this->config->getSecret(),
                ],
                'http' => [
                    'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
                    'timeout' => self::TIMEOUT_SECONDS,
                ],
            ]);
        });
    }
}
