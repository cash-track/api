<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\S3Bootloader;
use App\Config\S3Config;
use App\Service\Idempotency\RedisIdempotencyStore;
use Aws\S3\S3ClientInterface;
use Tests\Fixtures;
use Tests\TestCase;

/**
 * PUT /profile/photo uploads to S3 inline. Without a bound timeout the request could outlive its
 * idempotency lease and let a retry upload concurrently — this guards the timeout against being
 * removed or raised past the lease.
 */
class S3BootloaderTest extends TestCase
{
    public function testS3ClientHttpTimeoutStaysStrictlyUnderTheIdempotencyLease(): void
    {
        $config = $this->getContainer()->get(S3Config::class);

        $reflection = new \ReflectionClass($config);
        $reflection->getProperty('config')->setValue($config, [
            'region'   => Fixtures::string(),
            'endpoint' => Fixtures::url(),
            'key'      => Fixtures::string(),
            'secret'   => Fixtures::string(),
        ]);

        $bootloader = new S3Bootloader($config);
        $bootloader->boot($this->getContainer());

        $client = $this->getContainer()->get(S3ClientInterface::class);

        // getConfig() only surfaces client context params, not raw request options.
        // AwsClient::getCommand() merges the client's default request options into the built
        // command's '@http' key, so that's a public route to the same values without reflecting
        // into the private AwsClient::$defaultRequestOptions.
        $command = $client->getCommand('PutObject', ['Bucket' => Fixtures::string(), 'Key' => Fixtures::string()]);
        $httpOptions = $command['@http'];

        $this->assertIsArray($httpOptions);
        $this->assertArrayHasKey('timeout', $httpOptions);
        $this->assertArrayHasKey('connect_timeout', $httpOptions);
        $this->assertLessThan(RedisIdempotencyStore::LEASE_TTL, $httpOptions['timeout']);
        $this->assertLessThan($httpOptions['timeout'], $httpOptions['connect_timeout']);
    }
}
