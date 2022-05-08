<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use App\Repository\UserRepository;
use Spiral\Auth\TokenInterface;
use Tests\DatabaseTransaction;
use Tests\TestCase;

class UserRepositoryTest extends TestCase implements DatabaseTransaction
{
    public function testGetActorMissingSubject(): void
    {
        $repository = $this->getContainer()->get(UserRepository::class);

        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $token->method('getPayload')->willReturn([]);

        $this->assertNull($repository->getActor($token));
    }
}
