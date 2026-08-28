<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Request\Charge\CreateRequest;
use App\Service\Metrics\AppMetricsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

/**
 * Wiring checks: ChargeWalletService must feed app_charges_created_total / app_charges_deleted_total
 * and app_tag_assignments_total on the create and delete paths.
 */
final class ChargeMetricsTest extends TestCase implements DatabaseTransaction
{
    private UserFactory $userFactory;
    private WalletFactory $walletFactory;
    private ChargeFactory $chargeFactory;
    private TagFactory $tagFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
        $this->tagFactory = $this->getContainer()->get(TagFactory::class);
    }

    public function testChargeCreateRecordsChargeAndTagAssignments(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $charge = ChargeFactory::make();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementChargeCreated')->with($charge->type);
        $mock->expects($this->once())->method('incrementTagAssignments')->with(2);

        $this->withAuth($auth)->post("/v1/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
            'tags' => array_map(static fn ($tag) => $tag->id, $tags->toArray()),
            'dateTime' => $charge->createdAt->format(CreateRequest::DATE_FORMAT),
        ])->assertOk();
    }

    public function testChargeCreateWithoutTagsStillRecordsCharge(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = ChargeFactory::make();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementChargeCreated')->with($charge->type);
        $mock->expects($this->once())->method('incrementTagAssignments')->with(0);

        $this->withAuth($auth)->post("/v1/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
            'dateTime' => $charge->createdAt->format(CreateRequest::DATE_FORMAT),
        ])->assertOk();
    }

    public function testChargeDeleteRecordsDeleted(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementChargeDeleted')->with($charge->type);

        $this->withAuth($auth)
            ->delete("/v1/wallets/{$wallet->id}/charges/{$charge->id}")
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
