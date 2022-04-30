<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Contract\Storage;
use Kreait\Firebase\Factory;
use Tests\TestCase;

class FirebaseBootloaderTest extends TestCase
{
    public function testResolve(): void
    {
        $firebase = $this->getContainer()->get(Factory::class);

        $this->assertInstanceOf(Auth::class, $firebase->createAuth());
        $this->assertInstanceOf(Database::class, $firebase->createDatabase());
        $this->assertInstanceOf(Storage::class, $firebase->createStorage());
    }
}
