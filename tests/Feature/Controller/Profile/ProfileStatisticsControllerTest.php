<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Profile;

use App\Database\Charge;
use App\Repository\CurrencyRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class ProfileStatisticsControllerTest extends TestCase implements DatabaseTransaction
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

    public function testChargesFlowRequireAuth(): void
    {
        $response = $this->get('/profile/statistics/charges-flow');

        $response->assertUnauthorized();
    }

    public function testChargesFlowNoDefaultCurrency(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $this->mock(CurrencyRepository::class, ['findByPK'], function (MockObject $mock) {
            $mock->method('findByPK')->willReturn(null);
        });

        $response = $this->withAuth($auth)->get('/profile/statistics/charges-flow');

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testChargesFlowReturnStats(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $this->walletFactory->forUser($user);
        $this->chargeFactory->forUser($user);

        $this->chargeFactory->createManyPerWallet(
            $this->walletFactory->createMany(10),
            10,
        );

        $response = $this->withAuth($auth)->get('/profile/statistics/charges-flow');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey(Charge::TYPE_INCOME, $body['data']);
        $this->assertArrayHasKey(Charge::TYPE_EXPENSE, $body['data']);
        $this->assertArrayHasKey('currency', $body['data']);
    }

    public function testCountersRequireAuth(): void
    {
        $response = $this->get('/profile/statistics/counters');

        $response->assertUnauthorized();
    }

    public function testCountersReturnStats(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $walletsAmount = 10;
        $chargesPerWalletAmount = 10;

        $this->walletFactory->forUser($user);
        $this->chargeFactory->forUser($user);

        $this->walletFactory->create(WalletFactory::archived());

        $charges = $this->chargeFactory->createManyPerWallet(
            $this->walletFactory->createMany($walletsAmount),
            $chargesPerWalletAmount,
        );

        $incomeCharges = $charges->filter(fn (Charge $charge) => $charge->type === Charge::TYPE_INCOME)->count();

        $response = $this->withAuth($auth)->get('/profile/statistics/counters');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertEquals([
            'data' => [
                'wallets' => $walletsAmount + 1,
                'walletsArchived' => 1,
                'charges' => $walletsAmount * $chargesPerWalletAmount,
                'chargesIncome' => $incomeCharges,
            ],
        ], $body);
    }

    public function testWalletsLatestRequireAuth(): void
    {
        $response = $this->get('/profile/wallets/latest');

        $response->assertUnauthorized();
    }

    public function testWalletLatestReturnLatestWallets(): void
    {
        $latestWalletsAmount = 4;
        $latestChargesPerWalletAmount = 4;

        $auth = $this->makeAuth($user = $this->userFactory->create());

        $this->walletFactory->forUser($user);
        $this->chargeFactory->forUser($user);

        $this->chargeFactory->createManyPerWallet(
            $this->walletFactory->createMany($latestWalletsAmount * 2),
            $latestChargesPerWalletAmount * 3,
        );

        $response = $this->withAuth($auth)->get('/profile/wallets/latest');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertCount($latestWalletsAmount, $body['data']);

        foreach ($body['data'] as $bodyWallet) {
            $this->assertArrayHasKey('latestCharges', $bodyWallet);
            $this->assertIsArray($bodyWallet['latestCharges']);
            $this->assertCount($latestChargesPerWalletAmount, $bodyWallet['latestCharges']);
        }
    }
}
