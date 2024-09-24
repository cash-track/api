<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Service\Encrypter\EncrypterInterface;
use App\Security\UniqueChecker;
use Cycle\ORM\ORMInterface;
use Tests\Fixtures;
use Tests\TestCase;

class UniqueCheckerTest extends TestCase
{
    public function testVerifyEmptyRole(): void
    {
        $checker = new UniqueChecker(
            $this->getContainer()->get(ORMInterface::class),
            $this->getContainer()->get(EncrypterInterface::class)
        );

        $this->assertFalse($checker->verify(Fixtures::string(), '', Fixtures::string()));
    }
}
