<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\S3Config;
use Tests\Fixtures;
use Tests\TestCase;

class S3ConfigTest extends TestCase
{
    public function testConfigs(): void
    {
        $config = $this->getContainer()->get(S3Config::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'region'   => Fixtures::string(),
            'endpoint' => Fixtures::url(),
            'key'      => Fixtures::string(),
            'secret'   => Fixtures::string(),
        ]);

        $this->assertNotEmpty($config->getRegion());
        $this->assertNotEmpty($config->getEndpoint());
        $this->assertNotEmpty($config->getKey());
        $this->assertNotEmpty($config->getSecret());
    }
}
