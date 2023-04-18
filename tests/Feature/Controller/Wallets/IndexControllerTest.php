<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Database\Charge;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class IndexControllerTest extends TestCase implements DatabaseTransaction
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

    public function testIndexRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->get("/wallets/{$wallet->id}");

        $response->assertUnauthorized();
    }

    public function testIndexMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}");

        $response->assertUnauthorized();
    }

    public function testIndexMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}");

        $response->assertNotFound();
    }

    public function testIndexWalletForNonMemberNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}");

        $response->assertNotFound();
    }

    public function testIndexWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($wallet->id, $body, 'data.id');
        $this->assertArrayContains($wallet->name, $body, 'data.name');
        $this->assertArrayContains($wallet->slug, $body, 'data.slug');
    }

    public function testIndexTotalRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->get("/wallets/{$wallet->id}/total");

        $response->assertUnauthorized();
    }

    public function testIndexTotalMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/total");

        $response->assertUnauthorized();
    }

    public function testIndexTotalMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/total");

        $response->assertNotFound();
    }

    public function testIndexTotalWalletForNonMemberNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertNotFound();
    }

    public function testIndexTotalEmptyWalletTotal(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(0, $body, 'data.totalAmount');
        $this->assertArrayContains(0, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains(0, $body, 'data.totalExpenseAmount');
    }

    public function testIndexTotalWalletTotal(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $this->chargeFactory->forUser($user)->forWallet($wallet);

        $chargesAmount = rand(5, 20);
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        for ($i = 0; $i < $chargesAmount; $i++) {
            $charge = $this->chargeFactory->create();

            if ($charge->type === Charge::TYPE_INCOME) {
                $totalIncome += $charge->amount;
            } else {
                $totalExpense += $charge->amount;
            }
        }

        $wallet->totalAmount = $total = round($totalIncome - $totalExpense, 2);
        $this->walletFactory->persist($wallet);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($total, $body, 'data.totalAmount');
        $this->assertArrayContains($totalIncome, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains($totalExpense, $body, 'data.totalExpenseAmount');
    }

    public function totalWithDateFiltersReturnsFilteredTotalByTagDataProvider(): array
    {
        return [
            [
                [
                    'total' => 200,
                    'income' => 410,
                    'expense' => 210,
                ],
                [
                    'date-from' => '00-13-2022',
                    'date-to' => '40-00-2022',
                ]
            ],
            [
                [
                    'total' => 200,
                    'income' => 410,
                    'expense' => 210,
                ],
                [
                    'date-from' => '01-06-2022',
                    'date-to' => '04-06-2022',
                ]
            ],
            [
                [
                    'total' => 100,
                    'income' => 205,
                    'expense' => 105,
                ],
                [
                    'date-from' => '02-06-2022',
                    'date-to' => '03-06-2022',
                ]
            ],
            [
                [
                    'total' => 150,
                    'income' => 309,
                    'expense' => 159,
                ],
                [
                    'date-from' => '02-06-2022',
                ]
            ],
            [
                [
                    'total' => 150,
                    'income' => 306,
                    'expense' => 156,
                ],
                [
                    'date-to' => '03-06-2022',
                ]
            ],
        ];
    }

    /**
     * @dataProvider totalWithDateFiltersReturnsFilteredTotalByTagDataProvider
     * @param array $total
     * @param array $query
     * @return void
     */
    public function testTotalWithDateFiltersReturnsFilteredTotalByTag(array $total, array $query): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_INCOME;
            $charge->amount = 100 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->create($charge);
        }

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_EXPENSE;
            $charge->amount = 50 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->create($charge);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($total['total'], $body, 'data.totalAmount');
        $this->assertArrayContains($total['income'], $body, 'data.totalIncomeAmount');
        $this->assertArrayContains($total['expense'], $body, 'data.totalExpenseAmount');
    }

    public function testTotalWithDateFiltersDoesNotOverlapsBetweenRequests()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_INCOME;
            $charge->amount = 100 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->create($charge);
        }

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_EXPENSE;
            $charge->amount = 50 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->create($charge);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total", [
            'date-from' => '02-06-2022',
            'date-to' => '03-06-2022',
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(100, $body, 'data.totalAmount');
        $this->assertArrayContains(205, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains(105, $body, 'data.totalExpenseAmount');

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(200, $body, 'data.totalAmount');
        $this->assertArrayContains(410, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains(210, $body, 'data.totalExpenseAmount');
    }
}
