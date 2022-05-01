<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\FirebaseBootloader;
use App\Config\FirebaseConfig;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Storage;
use Kreait\Firebase\Factory;
use Tests\Fixtures;
use Tests\TestCase;

class FirebaseBootloaderTest extends TestCase
{
    public function testResolve(): void
    {
        $config = $this->getContainer()->get(FirebaseConfig::class);

        $class = new \ReflectionClass($config);
        $class->getProperty('config')->setValue($config, [
            'databaseUri'             => Fixtures::url(),
            'storageBucket'           => Fixtures::string(),
            'projectId'               => Fixtures::string(),
            'privateKeyId'            => Fixtures::string(),
            'privateKey'              => Fixtures::string(),
            'clientEmail'             => Fixtures::email(),
            'clientId'                => Fixtures::string(),
            'authUri'                 => Fixtures::url(),
            'tokenUri'                => Fixtures::url(),
            'authProviderX509CertUrl' => Fixtures::url(),
            'clientX509CertUrl'       => Fixtures::url(),
        ]);

        $bootloader = new FirebaseBootloader($config);
        $bootloader->boot($this->getContainer());

        $firebase = $this->getContainer()->get(Factory::class);

        $this->assertInstanceOf(Auth::class, $firebase->createAuth());
        $this->assertInstanceOf(Database::class, $firebase->createDatabase());
        $this->assertInstanceOf(Storage::class, $firebase->createStorage());
    }
}
