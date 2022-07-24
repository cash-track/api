<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Tags;

use App\Database\Charge;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ChargesControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected ChargeFactory $chargeFactory;

    protected TagFactory $tagFactory;

    const LIST_PER_PAGE = 25;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
        $this->tagFactory = $this->getContainer()->get(TagFactory::class);
    }

    public function testListRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($user = $this->userFactory->create())->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();

        $response = $this->get("/tags/{$tag->id}/charges");

        $response->assertUnauthorized();
    }

    public function testListMissingTagStillRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($user = $this->userFactory->create())->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();
        $tagId = Fixtures::integer();

        $response = $this->get("/tags/{$tagId}/charges");

        $response->assertUnauthorized();
    }

    public function testListMissingTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();
        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/tags/{$tagId}/charges");

        $response->assertNotFound();
    }

    public function testListForeignTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges");

        $response->assertNotFound();
    }

    public function testListNoCharges(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
        $this->assertArrayContains(0, $body, 'pagination.count');
        $this->assertArrayContains(0, $body, 'pagination.countDisplayed');
        $this->assertArrayContains(1, $body, 'pagination.page');
        $this->assertArrayContains(1, $body, 'pagination.pages');
        $this->assertArrayContains(self::LIST_PER_PAGE, $body, 'pagination.perPage');
        $this->assertArrayContains(null, $body, 'pagination.nextPage');
        $this->assertArrayContains(null, $body, 'pagination.previousPage');
    }

    public function testListReturnPaginatedCharges(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $clearCharges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(4)->toArray();

        $tag = $this->tagFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->createMany(self::LIST_PER_PAGE + 1)->toArray();

        usort($charges, fn(Charge $a, Charge $b) => $b->createdAt->getTimestamp() <=> $a->createdAt->getTimestamp());

        $charges = new ArrayCollection($charges);

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(self::LIST_PER_PAGE + 1, $body, 'pagination.count');
        $this->assertArrayContains(self::LIST_PER_PAGE, $body, 'pagination.countDisplayed');
        $this->assertArrayContains(1, $body, 'pagination.page');
        $this->assertArrayContains(2, $body, 'pagination.pages');
        $this->assertArrayContains(self::LIST_PER_PAGE, $body, 'pagination.perPage');
        $this->assertArrayContains(2, $body, 'pagination.nextPage');
        $this->assertArrayContains(null, $body, 'pagination.previousPage');

        foreach ($charges->slice(0, self::LIST_PER_PAGE) as $charge) {
            /** @var \App\Database\Charge $charge */
            $this->assertArrayContains((string) $charge->id, $body, 'data.*.id');
            $this->assertArrayContains($charge->title, $body, 'data.*.title');
            $this->assertArrayContains($charge->type, $body, 'data.*.operation');
            $this->assertArrayContains($wallet->id, $body, 'data.*.wallet.id');
            $this->assertArrayContains($wallet->name, $body, 'data.*.wallet.name');
        }

        foreach ($charges->slice(self::LIST_PER_PAGE, 1) as $charge) {
            /** @var \App\Database\Charge $charge */
            $this->assertArrayNotContains((string) $charge->id, $body, 'data.*.id');
            $this->assertArrayNotContains($charge->title, $body, 'data.*.title');
            $this->assertArrayNotContains($charge->type, $body, 'data.*.type');
        }

        foreach ($clearCharges as $charge) {
            /** @var \App\Database\Charge $charge */
            $this->assertArrayNotContains((string) $charge->id, $body, 'data.*.id');
            $this->assertArrayNotContains($charge->title, $body, 'data.*.title');
            $this->assertArrayNotContains($charge->type, $body, 'data.*.type');
        }
    }

    public function listWithDateFilterReturnFilteredChargesDataProvider(): array
    {
        return [
            [
                [1, 2, 3, 4],
                [
                    'date-from' => '00-13-2022',
                    'date-to' => '40-00-2022',
                ],
            ],
            [
                [1, 2, 3, 4],
                [
                    'date-from' => '01-06-2022',
                    'date-to' => '04-06-2022',
                ],
            ],
            [
                [2, 3],
                [
                    'date-from' => '02-06-2022',
                    'date-to' => '03-06-2022',
                ],
            ],
            [
                [1, 2, 3],
                ['date-to' => '03-06-2022'],
            ],
            [
                [2, 3, 4],
                ['date-from' => '02-06-2022'],
            ],
        ];
    }

    /**
     * @dataProvider listWithDateFilterReturnFilteredChargesDataProvider
     * @param array $expectedIndexes
     * @param array $query
     * @return void
     */
    public function testListWithDateFilterReturnFilteredCharges(array $expectedIndexes, array $query): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $tag = $this->tagFactory->forUser($user)->create();
        $wallet = $this->walletFactory->forUser($user)->create();

        $charges = [];

        for ($i = 1; $i <= 4; $i++) {
            $charges[$i] = ChargeFactory::make();
            $charges[$i]->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $charges[$i] = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create($charges[$i]);
        }

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertCount(count($expectedIndexes), $body['data']);

        foreach ($expectedIndexes as $index) {
            if (! array_key_exists($index, $charges)) {
                continue;
            }

            $this->assertArrayContains((string) $charges[$index]->id, $body, 'data.*.id');
            $this->assertArrayContains($charges[$index]->title, $body, 'data.*.title');
        }
    }

    public function testTotalRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();

        $response = $this->get("/tags/{$tag->id}/charges/total");

        $response->assertUnauthorized();
    }

    public function testTotalOfMissingTagNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/tags/{$tagId}/charges/total");

        $response->assertNotFound();
    }

    public function testTotalOfForeignTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/total");

        $response->assertNotFound();
    }

    public function testTotalOfNoChargesWithTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(3);
        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(0, $body, 'data.totalAmount');
        $this->assertArrayContains(0, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains(0, $body, 'data.totalExpenseAmount');
    }

    public function testTotalReturnsTotalByTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(4);

        $chargesAmount = rand(5, 20);
        $totalIncome = 0.00;
        $totalExpense = 0.00;

        $this->chargeFactory->withTags([$tag]);

        for ($i = 0; $i < $chargesAmount; $i++) {
            $charge = $this->chargeFactory->create();

            if ($charge->type === Charge::TYPE_INCOME) {
                $totalIncome += $charge->amount;
            } else {
                $totalExpense += $charge->amount;
            }
        }

        $total = round($totalIncome - $totalExpense, 2);

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/total");

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
        $tag = $this->tagFactory->forUser($user)->create();
        $wallet = $this->walletFactory->forUser($user)->create();

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_INCOME;
            $charge->amount = 100 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create($charge);
        }

        for ($i = 1; $i <= 4; $i++) {
            $charge = ChargeFactory::make();
            $charge->type = Charge::TYPE_EXPENSE;
            $charge->amount = 50 + $i;
            $charge->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create($charge);
        }

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/total", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($total['total'], $body, 'data.totalAmount');
        $this->assertArrayContains($total['income'], $body, 'data.totalIncomeAmount');
        $this->assertArrayContains($total['expense'], $body, 'data.totalExpenseAmount');
    }

    public function testGraphRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();

        $response = $this->get("/tags/{$tag->id}/charges/graph");

        $response->assertUnauthorized();
    }

    public function testGraphOfMissingTagNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/tags/{$tagId}/charges/graph");

        $response->assertNotFound();
    }

    public function testGraphOfForeignTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/graph");

        $response->assertNotFound();
    }

    public function testGraphOfNoChargesWithTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(3);
        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/graph");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
    }

    public function graphReturnsGraphDataByTagDataProvider(): array
    {
        $charges = [
            [
                'date' => '2022-05-31',
                'type' => Charge::TYPE_INCOME,
                'amount' => 2060,
            ],
            [
                'date' => '2022-06-01',
                'type' => Charge::TYPE_EXPENSE,
                'amount' => 150.99,
            ],
            [
                'date' => '2022-06-02',
                'type' => Charge::TYPE_EXPENSE,
                'amount' => 51.02,
            ],
            [
                'date' => '2022-06-03',
                'type' => Charge::TYPE_INCOME,
                'amount' => 30.99,
            ],
        ];

        return [
            [
                $charges,
                [],
                [
                    // date, income, expense
                    ['2022-05-01', 2060, 0],
                    ['2022-06-01', 30.99, 202.01],
                ]
            ],
            [
                $charges,
                [
                    'group-by' => 'day'
                ],
                [
                    ['2022-05-31', 2060, 0],
                    ['2022-06-01', 0, 150.99],
                    ['2022-06-02', 0, 51.02],
                    ['2022-06-03', 30.99, 0],
                ]
            ],
            [
                $charges,
                [
                    'date-from' => '2022-06-01',
                    'date-to' => '2022-06-04',
                ],
                [
                    ['2022-06-01', 30.99, 202.01],
                ]
            ],
            [
                $charges,
                [
                    'date-from' => '2022-06-01',
                    'date-to' => '2022-06-04',
                    'group-by' => 'day'
                ],
                [
                    ['2022-06-01', 0, 150.99],
                    ['2022-06-02', 0, 51.02],
                    ['2022-06-03', 30.99, 0],
                    ['2022-06-04', 0, 0],
                ]
            ],
            [
                $charges,
                [
                    'group-by' => 'year'
                ],
                [
                    ['2022-01-01', 2090.99, 202.01],
                ]
            ],
            [
                $charges,
                [
                    'date-from' => '2021-12-31',
                    'date-to' => '2022-06-04',
                    'group-by' => 'year'
                ],
                [
                    ['2022-01-01', 2090.99, 202.01],
                    ['2021-01-01', 0, 0],
                ]
            ],
        ];
    }

    /**
     * @dataProvider graphReturnsGraphDataByTagDataProvider
     * @param array $setCharges
     * @param array $query
     * @param array $expectedData
     * @return void
     * @throws \Exception
     */
    public function testGraphReturnsGraphDataByTag(array $setCharges, array $query, array $expectedData): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $this->chargeFactory->forUser($user)->forWallet($wallet);

        foreach ($setCharges as $item) {
            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $this->chargeFactory->withTags([])->create($charge);

            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $charge->type = $item['type'];
            $charge->amount = $item['amount'];
            $this->chargeFactory->withTags([$tag])->create($charge);
        }

        $response = $this->withAuth($auth)->get("/tags/{$tag->id}/charges/graph", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($expectedData), $body['data']);

        foreach ($expectedData as $expected) {
            $this->assertArrayContains($expected[0], $body, 'data.*.date');
            $this->assertArrayContains($expected[1], $body, 'data.*.income');
            $this->assertArrayContains($expected[2], $body, 'data.*.expense');
        }
    }
}
