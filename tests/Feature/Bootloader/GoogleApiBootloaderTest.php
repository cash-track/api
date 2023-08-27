<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\GoogleApiBootloader;
use App\Config\GoogleApiConfig;
use Google\Client;
use Tests\Fixtures;
use Tests\TestCase;

class GoogleApiBootloaderTest extends TestCase
{
    public function testResolve(): void
    {
        $config = $this->getContainer()->get(GoogleApiConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'clientId'                => Fixtures::string(),
            'clientSecret'            => Fixtures::string(),
            'projectId'               => Fixtures::string(),
            'authUri'                 => Fixtures::url(),
            'tokenUri'                => Fixtures::url(),
            'authProviderX509CertUrl' => Fixtures::url(),
            'redirectUris'            => [Fixtures::url()],
        ]);

        $bootloader = new GoogleApiBootloader($config);
        $bootloader->boot($this->getContainer());

        $client = $this->getContainer()->get(Client::class);

        $this->assertInstanceOf(Client::class, $client);
    }
}
