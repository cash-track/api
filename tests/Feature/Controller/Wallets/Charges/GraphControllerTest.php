<?php

declare(strict_types=1);

namespace Feature\Controller\Wallets\Charges;

use App\Database\Charge;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class GraphControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected ChargeFactory $chargeFactory;

    protected TagFactory $tagFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
        $this->tagFactory = $this->getContainer()->get(TagFactory::class);
    }

    public function testAmountRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->get("/wallets/{$wallet->id}/charges/graph/amount");

        $response->assertUnauthorized();
    }

    public function testAmountOfMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/charges/graph/amount");

        $response->assertNotFound();
    }

    public function testAmountOfForeignWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/amount");

        $response->assertNotFound();
    }

    public function testAmountOfNoChargesWithWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/amount");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
    }

    public function amountReturnsGraphDataDataProvider(): array
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
     * @dataProvider amountReturnsGraphDataDataProvider
     * @param array $setCharges
     * @param array $query
     * @param array $expectedData
     * @return void
     * @throws \Exception
     */
    public function testAmountReturnsGraphData(array $setCharges, array $query, array $expectedData): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user);

        foreach ($setCharges as $item) {
            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $this->chargeFactory->forWallet($otherWallet)->create($charge);

            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $charge->type = $item['type'];
            $charge->amount = $item['amount'];
            $this->chargeFactory->forWallet($wallet)->create($charge);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/amount", $query);

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

    public function testAmountOfNoChargesWithWalletWithTags(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(3);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/amount", [
            'tags' => (string) $tag->id,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
    }

    public function amountWithTagsReturnsGraphDataDataProvider(): array
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
     * @dataProvider amountWithTagsReturnsGraphDataDataProvider
     * @param array $setCharges
     * @param array $query
     * @param array $expectedData
     * @return void
     * @throws \Exception
     */
    public function testAmountWithTagsReturnsGraphData(array $setCharges, array $query, array $expectedData): void
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

        $query['tags'] = (string) $tag->id;

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/amount", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($expectedData), $body['data']);

        foreach ($expectedData as $expected) {
            $this->assertArrayContains($expected[0], $body, 'data.*.date');
            $this->assertArrayContains($expected[1], $body, "data.*.tags.{$tag->id}.income");
            $this->assertArrayContains($expected[2], $body, "data.*.tags.{$tag->id}.expense");
        }
    }

    public function testTotalRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->get("/wallets/{$wallet->id}/charges/graph/total");

        $response->assertUnauthorized();
    }

    public function testTotalOfMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/charges/graph/total");

        $response->assertNotFound();
    }

    public function testTotalOfForeignWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/total");

        $response->assertNotFound();
    }

    public function testTotalOfNoChargesWithWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
    }

    public function totalReturnsGraphDataDataProvider(): array
    {
        [$tag1, $tag2, $tag3, $tag4] = [TagFactory::make(), TagFactory::make(), TagFactory::make(), TagFactory::make()];
        $tag1->id = 1001;
        $tag2->id = 1002;
        $tag3->id = 1003;
        $tag4->id = 1004;

        $charges = [
            [
                'date' => '2022-05-31',
                'type' => Charge::TYPE_INCOME,
                'amount' => 2060,
                'tags' => [$tag1, $tag2, $tag3],
            ],
            [
                'date' => '2022-06-01',
                'type' => Charge::TYPE_EXPENSE,
                'amount' => 150.99,
                'tags' => [$tag1],
            ],
            [
                'date' => '2022-06-02',
                'type' => Charge::TYPE_EXPENSE,
                'amount' => 51.02,
                'tags' => [$tag2, $tag3],
            ],
            [
                'date' => '2022-06-03',
                'type' => Charge::TYPE_INCOME,
                'amount' => 30.99,
                'tags' => [],
            ],
        ];

        return [
            [
                $charges,
                ['charge-type' => 'income'],
                [
                    // total, tags
                    [2060, [1001, 1002, 1003]],
                    [30.99, []],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'expense'],
                [
                    // total, tags
                    [150.99, [1001]],
                    [51.02, [1002, 1003]],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'expense', 'date-from' => '2022-06-02'],
                [
                    // total, tags
                    [51.02, [1002, 1003]],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'expense', 'date-to' => '2022-06-01'],
                [
                    // total, tags
                    [150.99, [1001]],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'expense', 'date-from' => '2022-06-01', 'date-to' => '2022-06-02'],
                [
                    // total, tags
                    [150.99, [1001]],
                    [51.02, [1002, 1003]],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'expense', 'date-from' => '2022-06-01', 'date-to' => '2022-06-02', 'tags' => '1001'],
                [
                    // total, tags
                    [150.99, [1001]],
                ]
            ],
            [
                $charges,
                ['charge-type' => 'invalid', 'date-from' => '2022-05-31', 'date-to' => '2022-06-02'],
                [
                    // total, tags
                    [2060, [1001, 1002, 1003]],
                    [150.99, [1001]],
                    [51.02, [1002, 1003]],
                ]
            ],
            [
                $charges,
                ['date-from' => '2022-05-31', 'date-to' => '2022-06-02'],
                [
                    // total, tags
                    [2060, [1001, 1002, 1003]],
                    [150.99, [1001]],
                    [51.02, [1002, 1003]],
                ]
            ],
            [
                $charges,
                ['date-from' => '2023-05-31', 'date-to' => '2023-06-02'],
                []
            ],
            [
                $charges,
                [],
                [
                    // total, tags
                    [2060, [1001, 1002, 1003]],
                    [150.99, [1001]],
                    [51.02, [1002, 1003]],
                    [30.99, []],
                ]
            ],
        ];
    }

    /**
     * @dataProvider totalReturnsGraphDataDataProvider
     * @param array $setCharges
     * @param array $query
     * @param array $expectedData
     * @return void
     * @throws \Exception
     */
    public function testTotalReturnsGraphData(array $setCharges, array $query, array $expectedData): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user);

        foreach ($setCharges as &$item) {
            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $this->chargeFactory->forWallet($otherWallet)->create($charge);

            $charge = ChargeFactory::make();
            $charge->createdAt = new \DateTimeImmutable($item['date']);
            $charge->type = $item['type'];
            $charge->amount = $item['amount'];

            foreach ($item['tags'] as &$tag) {
                $tag = $this->tagFactory->forUser($user)->create($tag);
            }

            $this->chargeFactory->forWallet($wallet)->withTags($item['tags'])->create($charge);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges/graph/total", $query);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($expectedData), $body['data']);

        foreach ($expectedData as $expected) {
            $this->assertArrayContains($expected[0], $body, 'data.*.amount');
            foreach ($expected[1] as $expectedTagId) {
                $this->assertArrayContains($expectedTagId, $body, 'data.*.tags.*');
            }
        }
    }
}
