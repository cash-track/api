<?php

declare(strict_types=1);

namespace Tests\Feature\Wallets;

use App\Database\Wallet;
use App\Service\WalletService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ActiveControllerTest extends TestCase implements DatabaseTransaction
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

    public function testActivateRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->post("/wallets/{$wallet->id}/activate");

        $response->assertUnauthorized();
    }

    public function testActivateNotExistingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->post("/wallets/{$walletId}/activate");

        $response->assertUnauthorized();
    }

    public function testActivateNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/activate");

        $response->assertNotFound();
    }

    public function activeAndInactiveWalletProvider()
    {
        return [
            [WalletFactory::make()],
            [WalletFactory::disabled()],
        ];
    }

    /**
     * @dataProvider activeAndInactiveWalletProvider
     * @param \App\Database\Wallet $wallet
     * @return void
     */
    public function testActivateWallet(Wallet $wallet): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/activate");

        $response->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'is_active' => true,
        ]);
    }

    public function testActivateWalletThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create(WalletFactory::disabled());

        $this->mock(WalletService::class, ['activate'], function (MockObject $mock) {
            $mock->method('activate')->willThrowException(new \RuntimeException('Storage exception'));
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/activate");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('wallets', [
            'id' => $wallet->id,
            'is_active' => true,
        ]);
    }

    public function testDisableRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->post("/wallets/{$wallet->id}/disable");

        $response->assertUnauthorized();
    }

    public function testDisableNotExistingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->post("/wallets/{$walletId}/disable");

        $response->assertUnauthorized();
    }

    public function testDisableNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/disable");

        $response->assertNotFound();
    }

    /**
     * @dataProvider activeAndInactiveWalletProvider
     * @param \App\Database\Wallet $wallet
     * @return void
     */
    public function testDisableWallet(Wallet $wallet): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/disable");

        $response->assertOk();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'is_active' => false,
        ]);
    }

    public function testDisableWalletThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $this->mock(WalletService::class, ['disable'], function (MockObject $mock) {
            $mock->method('disable')->willThrowException(new \RuntimeException('Storage exception'));
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/disable");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('wallets', [
            'id' => $wallet->id,
            'is_active' => false,
        ]);
    }
}
