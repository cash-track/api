<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\S3Config;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

/**
 * Note: It uses ~10MB of memory
 */
final class S3Bootloader extends Bootloader
{
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
            ]);
        });
    }
}
