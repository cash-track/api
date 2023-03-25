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
class S3Bootloader extends Bootloader
{
    /**
     * @var \App\Config\S3Config
     */
    private $config;

    /**
     * FirebaseBootloader constructor.
     *
     * @param \App\Config\S3Config $config
     */
    public function __construct(S3Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Spiral\Core\Container $container
     * @return void
     */
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
