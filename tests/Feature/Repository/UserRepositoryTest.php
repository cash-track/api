<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use App\Repository\UserRepository;
use Spiral\Auth\TokenInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class UserRepositoryTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function testGetActorMissingSubject(): void
    {
        $repository = $this->getContainer()->get(UserRepository::class);

        $token = $this->getMockBuilder(TokenInterface::class)->getMock();

        $token->method('getPayload')->willReturn([]);

        $this->assertNull($repository->getActor($token));
    }

    public function testCountByEmailConfirmedCountsUsersByEmailState(): void
    {
        /** @var UserRepository $repository */
        $repository = $this->getContainer()->get(UserRepository::class);

        $confirmedBefore = $repository->countByEmailConfirmed(true);
        $unconfirmedBefore = $repository->countByEmailConfirmed(false);

        $firstConfirmed = UserFactory::make();
        $firstConfirmed->isEmailConfirmed = true;
        $this->userFactory->create($firstConfirmed);

        $secondConfirmed = UserFactory::make();
        $secondConfirmed->isEmailConfirmed = true;
        $this->userFactory->create($secondConfirmed);

        $unconfirmed = UserFactory::make();
        $unconfirmed->isEmailConfirmed = false;
        $this->userFactory->create($unconfirmed);

        $this->assertSame($confirmedBefore + 2, $repository->countByEmailConfirmed(true));
        $this->assertSame($unconfirmedBefore + 1, $repository->countByEmailConfirmed(false));
    }

    public function testCountActiveSinceCountsOnlyUsersActiveAtOrAfterTheCutoff(): void
    {
        /** @var UserRepository $repository */
        $repository = $this->getContainer()->get(UserRepository::class);

        $cutoff = new \DateTimeImmutable('-7 days');
        $before = $repository->countActiveSince($cutoff);

        $activeYesterday = UserFactory::make();
        $activeYesterday->activeAt = new \DateTimeImmutable('-1 day');
        $this->userFactory->create($activeYesterday);

        $activeThreeDaysAgo = UserFactory::make();
        $activeThreeDaysAgo->activeAt = new \DateTimeImmutable('-3 days');
        $this->userFactory->create($activeThreeDaysAgo);

        $stale = UserFactory::make();
        $stale->activeAt = new \DateTimeImmutable('-30 days');
        $this->userFactory->create($stale);

        $neverActive = UserFactory::make();
        $neverActive->activeAt = null;
        $this->userFactory->create($neverActive);

        $this->assertSame($before + 2, $repository->countActiveSince($cutoff));
    }
}
