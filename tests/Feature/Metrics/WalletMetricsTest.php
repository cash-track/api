<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Service\Metrics\AppMetricsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

/**
 * Wiring checks: WalletService must feed app_wallets_created_total on create and
 * app_wallets_archived_total only on a real un-archived -> archived transition.
 */
final class WalletMetricsTest extends TestCase implements DatabaseTransaction
{
    private UserFactory $userFactory;
    private WalletFactory $walletFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
    }

    public function testWalletCreateRecordsCreated(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $wallet = WalletFactory::make();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementWalletCreated');

        $this->withAuth($auth)->post('/v1/wallets', [
            'name' => $wallet->name,
            'slug' => $wallet->slug,
            'isPublic' => $wallet->isPublic,
            'defaultCurrencyCode' => $wallet->defaultCurrencyCode,
        ])->assertOk();
    }

    public function testWalletArchiveRecordsArchived(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create(WalletFactory::make());

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementWalletArchived');

        $this->withAuth($auth)
            ->post("/v1/wallets/{$wallet->id}/archive")
            ->assertOk();
    }

    public function testArchivingAnAlreadyArchivedWalletIsNotCounted(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $mock = $this->mockMetrics();
        $mock->expects($this->never())->method('incrementWalletArchived');

        $this->withAuth($auth)
            ->post("/v1/wallets/{$wallet->id}/archive")
            ->assertOk();
    }

    /**
     * @return AppMetricsInterface&MockObject
     */
    private function mockMetrics(): AppMetricsInterface&MockObject
    {
        $mock = $this->getMockBuilder(AppMetricsInterface::class)->getMock();

        $this->getContainer()->bind(AppMetricsInterface::class, static fn () => $mock);

        return $mock;
    }
}
