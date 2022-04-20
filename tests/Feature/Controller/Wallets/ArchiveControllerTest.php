<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Database\Wallet;
use App\Service\WalletService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ArchiveControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected ChargeFactory $chargeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
    }

    public function testArchiveRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->post("/wallets/{$wallet->id}/archive");

        $response->assertUnauthorized();
    }

    public function testArchiveNotExistingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->post("/wallets/{$walletId}/archive");

        $response->assertUnauthorized();
    }

    public function testArchiveNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/archive");

        $response->assertNotFound();
    }

    public function unArchivedAndArchivedWalletProvider()
    {
        return [
            [WalletFactory::make()],
            [WalletFactory::archived()],
        ];
    }

    /**
     * @dataProvider unArchivedAndArchivedWalletProvider
     * @param \App\Database\Wallet $wallet
     * @return void
     */
    public function testArchiveWallet(Wallet $wallet): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/archive");

        $response->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'is_archived' => true,
        ]);
    }

    public function testArchiveWalletThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $this->mock(WalletService::class, ['archive'], function (MockObject $mock) {
            $mock->method('archive')->willThrowException(new \RuntimeException('Storage exception'));
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/archive");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('wallets', [
            'id' => $wallet->id,
            'is_archived' => false,
        ]);
    }

    public function testUnArchiveRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->post("/wallets/{$wallet->id}/un-archive");

        $response->assertUnauthorized();
    }

    public function testUnArchiveNotExistingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->post("/wallets/{$walletId}/un-archive");

        $response->assertUnauthorized();
    }

    public function testUnArchiveNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/un-archive");

        $response->assertNotFound();
    }

    /**
     * @dataProvider unArchivedAndArchivedWalletProvider
     * @param \App\Database\Wallet $wallet
     * @return void
     */
    public function testUnArchiveWallet(Wallet $wallet): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/un-archive");

        $response->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'is_archived' => false,
        ]);
    }

    public function testUnArchiveWalletThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $this->mock(WalletService::class, ['unArchive'], function (MockObject $mock) {
            $mock->method('unArchive')->willThrowException(new \RuntimeException('Storage exception'));
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/un-archive");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('wallets', [
            'id' => $wallet->id,
            'is_archived' => false,
        ]);
    }
}
