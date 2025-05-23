<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets\Charges;

use App\Database\Charge;
use App\Database\Tag;
use App\Request\Charge\CreateRequest;
use App\Service\ChargeWalletService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
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

    const int LIST_PER_PAGE = 25;

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
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->get("/wallets/{$wallet->id}/charges");

        $response->assertUnauthorized();
    }

    public function testListMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/charges");

        $response->assertUnauthorized();
    }

    public function testListMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/charges");

        $response->assertNotFound();
    }

    public function testListNotMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges");

        $response->assertNotFound();
    }

    public function testListNoCharges(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges");

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
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(self::LIST_PER_PAGE + 1)->toArray();

        usort($charges, fn(Charge $a, Charge $b) => $b->createdAt->getTimestamp() <=> $a->createdAt->getTimestamp());

        $charges = new ArrayCollection($charges);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges");

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
        }

        foreach ($charges->slice(self::LIST_PER_PAGE, 1) as $charge) {
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
        $wallet = $this->walletFactory->forUser($user)->create();

        $charges = [];

        for ($i = 1; $i <= 4; $i++) {
            $charges[$i] = ChargeFactory::make();
            $charges[$i]->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $charges[$i] = $this->chargeFactory->forUser($user)->forWallet($wallet)->create($charges[$i]);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges", $query);

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

    public function testListWithTagsFilterNoCharges(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges", [
            'tags' => (string) $tag->id,
        ]);

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

    public function testListWithTagsFilterReturnPaginatedCharges(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $clearCharges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(4)->toArray();

        $tag = $this->tagFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->createMany(self::LIST_PER_PAGE + 1)->toArray();

        usort($charges, fn(Charge $a, Charge $b) => $b->createdAt->getTimestamp() <=> $a->createdAt->getTimestamp());

        $charges = new ArrayCollection($charges);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges", [
            'tags' => (string) $tag->id,
        ]);

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

    public function listWithTagsFilterWithDateFilterReturnFilteredChargesDataProvider(): array
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
     * @dataProvider listWithTagsFilterWithDateFilterReturnFilteredChargesDataProvider
     * @param array $expectedIndexes
     * @param array $query
     * @return void
     */
    public function testListWithTagsFilterWithDateFilterReturnFilteredCharges(array $expectedIndexes, array $query): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $charges = [];

        for ($i = 1; $i <= 4; $i++) {
            $charges[$i] = ChargeFactory::make();
            $charges[$i]->createdAt = new \DateTimeImmutable("0{$i}-06-2022");
            $charges[$i] = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->create($charges[$i]);
        }

        $query['tags'] = (string) $tag->id;

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/charges", $query);

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

    public function testCreateRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $charge = ChargeFactory::make();

        $response = $this->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertUnauthorized();
    }

    public function testCreateMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $charge = ChargeFactory::make();

        $response = $this->post("/wallets/{$walletId}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertUnauthorized();
    }

    public function testCreateMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $charge = ChargeFactory::make();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertNotFound();
    }

    public function testCreateNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $charge = ChargeFactory::make();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertNotFound();
    }

    public function createValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'title']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'title' => false,
                'description' => false,
                'dateTime' => 123,
            ], ['type', 'amount', 'title', 'description', 'dateTime']],
            [[
                'type' => '+',
                'amount' => 0,
                'title' => 'Title',
                'dateTime' => '123'
            ], ['amount', 'dateTime']],
            [[
                'type' => '+',
                'amount' => -1,
                'title' => 'Title',
                'dateTime' => (new \DateTimeImmutable())->add(\DateInterval::createFromDateString('1 minute'))->format(CreateRequest::DATE_FORMAT),
            ], ['amount']],
        ];
    }

    /**
     * @dataProvider createValidationFailsDataProvider
     * @param array $request
     * @param array $expectedErrors
     * @return void
     */
    public function testCreateValidationFails(array $request, array $expectedErrors): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testCreateStoreCharge(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
            'dateTime' => $charge->createdAt->format(CreateRequest::DATE_FORMAT),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($charge->title, $body, 'data.title');
        $this->assertArrayContains($charge->type, $body, 'data.operation');
        $this->assertArrayContains($charge->amount, $body, 'data.amount');
        $this->assertArrayContains($charge->description, $body, 'data.description');
        $this->assertArrayContains($charge->createdAt->format(DATE_W3C), $body, 'data.dateTime');

        $this->assertDatabaseHas('charges', [
            'title' => $charge->title,
            'type' => $charge->type,
            'amount' => $charge->amount,
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'total_amount' => $charge->type === Charge::TYPE_INCOME ? $charge->amount : -1 * $charge->amount,
        ]);
    }

    public function testCreateStoreChargeWithTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();

        $tags = $this->tagFactory->forUser($user)->createMany(3);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'tags' => $tags->map(fn(Tag $tag) => $tag->id)->getValues(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertNotEmpty($body['data']['id'] ?? null);

        foreach ($tags as $tag) {
            $this->assertArrayContains($tag->id, $body, 'data.tags.*.id');
            $this->assertArrayContains($tag->name, $body, 'data.tags.*.name');

            $this->assertDatabaseHas('tag_charges', [
                'tag_id' => $tag->id,
                'charge_id' => $body['data']['id'],
            ]);
        }
    }

    public function testCreateStoreChargeWithForeignTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();

        $tags = $this->tagFactory->forUser($this->userFactory->create())->createMany(3);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'tags' => $tags->map(fn(Tag $tag) => $tag->id)->getValues(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertNotEmpty($body['data']['id'] ?? null);

        foreach ($tags as $tag) {
            $this->assertArrayNotContains($tag->id, $body, 'data.tags.*.id');
            $this->assertArrayNotContains($tag->name, $body, 'data.tags.*.name');

            $this->assertDatabaseMissing('tag_charges', [
                'tag_id' => $tag->id,
                'charge_id' => $body['data']['id'],
            ]);
        }
    }

    public function testCreateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();

        $this->mock(ChargeWalletService::class, ['create'], function (MockObject $mock) {
            $mock->expects($this->once())->method('create')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    public function testUpdateRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $updatedCharge = ChargeFactory::make();

        $response = $this->put("/wallets/{$wallet->id}/charges/{$charge->id}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletAndChargeStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $chargeId = Fixtures::string();

        $updatedCharge = ChargeFactory::make();

        $response = $this->put("/wallets/{$walletId}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingChargeStillRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();
        $chargeId = Fixtures::string();

        $updatedCharge = ChargeFactory::make();

        $response = $this->put("/wallets/{$wallet->id}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletAndChargeReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();
        $chargeId = Fixtures::string();

        $updatedCharge = ChargeFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$walletId}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertNotFound();
    }

    public function testUpdateMissingChargeReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $chargeId = Fixtures::string();

        $updatedCharge = ChargeFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertNotFound();
    }

    public function testUpdateNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $updatedCharge = ChargeFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$charge->id}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
        ]);

        $response->assertNotFound();
    }

    public function updateValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'title']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'title' => false,
                'description' => false,
                'dateTime' => 123,
            ], ['type', 'amount', 'title', 'description', 'dateTime']],
            [[
                'type' => '+',
                'amount' => 0,
                'title' => 'Title',
                'dateTime' => '123'
            ], ['amount', 'dateTime']],
            [[
                'type' => '+',
                'amount' => -1,
                'title' => 'Title',
                'dateTime' => (new \DateTimeImmutable())->add(\DateInterval::createFromDateString('1 minute'))->format(CreateRequest::DATE_FORMAT),
            ], ['amount']],
        ];
    }

    /**
     * @dataProvider updateValidationFailsDataProvider
     * @param array $request
     * @param array $expectedErrors
     * @return void
     */
    public function testUpdateValidationFails(array $request, array $expectedErrors): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$charge->id}", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testUpdateStoreCharge(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();
        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertOk();

        $chargeId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $updatedCharge = ChargeFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
            'dateTime' => $updatedCharge->createdAt->format(CreateRequest::DATE_FORMAT),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($updatedCharge->title, $body, 'data.title');
        $this->assertArrayContains($updatedCharge->type, $body, 'data.operation');
        $this->assertArrayContains($updatedCharge->amount, $body, 'data.amount');
        $this->assertArrayContains($updatedCharge->description, $body, 'data.description');

        $this->assertDatabaseHas('charges', [
            'title' => $updatedCharge->title,
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'total_amount' => $updatedCharge->type === Charge::TYPE_INCOME ? $updatedCharge->amount : -1 * $updatedCharge->amount,
        ]);
    }

    public function testUpdateStoreChargeWithTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();

        $tags = $this->tagFactory->forUser($user)->createMany(3);
        $newTags = $this->tagFactory->forUser($user)->createMany(2);
        $newTags->add($tags->first());

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
            'tags' => $tags->map(fn(Tag $tag) => $tag->id)->getValues(),
        ]);

        $response->assertOk();

        $chargeId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $updatedCharge = ChargeFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$chargeId}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
            'tags' => $newTags->map(fn(Tag $tag) => $tag->id)->getValues(),
            'dateTime' => $updatedCharge->createdAt->format(CreateRequest::DATE_FORMAT),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        foreach ($newTags as $tag) {
            $this->assertArrayContains($tag->id, $body, 'data.tags.*.id');
            $this->assertArrayContains($tag->name, $body, 'data.tags.*.name');

            $this->assertDatabaseHas('tag_charges', [
                'tag_id' => $tag->id,
                'charge_id' => $body['data']['id'],
            ]);
        }

        foreach ($tags->slice(1, 2) as $tag) {
            $this->assertArrayNotContains($tag->id, $body, 'data.tags.*.id');
            $this->assertArrayNotContains($tag->name, $body, 'data.tags.*.name');

            $this->assertDatabaseMissing('tag_charges', [
                'tag_id' => $tag->id,
                'charge_id' => $body['data']['id'],
            ]);
        }
    }

    public function testUpdateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $updatedCharge = ChargeFactory::make();

        $this->mock(ChargeWalletService::class, ['update'], function (MockObject $mock) {
            $mock->expects($this->once())->method('update')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/charges/{$charge->id}", [
            'type' => $updatedCharge->type,
            'amount' => $updatedCharge->amount,
            'title' => $updatedCharge->title,
            'description' => $updatedCharge->description,
            'dateTime' => $updatedCharge->createdAt->format(CreateRequest::DATE_FORMAT),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    public function testDeleteRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->delete("/wallets/{$wallet->id}/charges/{$charge->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletAndChargeStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $chargeId = Fixtures::string();

        $response = $this->delete("/wallets/{$walletId}/charges/{$chargeId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingChargeStillRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();
        $chargeId = Fixtures::string();

        $response = $this->delete("/wallets/{$wallet->id}/charges/{$chargeId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletAndChargeReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();
        $chargeId = Fixtures::string();

        $response = $this->withAuth($auth)->delete("/wallets/{$walletId}/charges/{$chargeId}");

        $response->assertNotFound();
    }

    public function testDeleteMissingChargeReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $chargeId = Fixtures::string();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/charges/{$chargeId}");

        $response->assertNotFound();
    }

    public function testDeleteNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/charges/{$charge->id}");

        $response->assertNotFound();
    }

    public function testDeleteRemoveCharge(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $charge = ChargeFactory::make();
        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges", [
            'type' => $charge->type,
            'amount' => $charge->amount,
            'title' => $charge->title,
            'description' => $charge->description,
        ]);

        $response->assertOk();

        $chargeId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/charges/{$chargeId}");

        $response->assertOk();

        $this->assertDatabaseMissing('charges', [
            'title' => $charge->title,
            'type' => $charge->type,
            'amount' => $charge->amount,
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'total_amount' => 0,
        ]);
    }

    public function testDeleteThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $charge = $this->chargeFactory->forUser($user)->forWallet($wallet)->create();

        $this->mock(ChargeWalletService::class, ['delete'], function (MockObject $mock) {
            $mock->expects($this->once())->method('delete')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/charges/{$charge->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    public function testMoveRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(10);
        $targetWallet = $this->walletFactory->forUser($user)->create();

        $response = $this->post("/wallets/{$wallet->id}/charges/move/{$targetWallet->id}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertUnauthorized();
    }

    public function testMoveMissingWalletsRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $targetWalletId = Fixtures::integer();
        $chargeId = Fixtures::string();

        $response = $this->post("/wallets/{$walletId}/charges/move/{$targetWalletId}", [
            'chargeIds' => [$chargeId],
        ]);

        $response->assertUnauthorized();
    }

    public function testMoveValidationFails(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $targetWallet = $this->walletFactory->forUser($user)->create();
        $chargeId = Fixtures::string();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges/move/{$targetWallet->id}", [
            'chargeIds' => [$chargeId],
        ]);

        $response->assertUnprocessable();
    }

    public function testMoveMissingWalletsReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(10);
        $walletId = Fixtures::integer();
        $targetWalletId = Fixtures::integer();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/charges/move/{$targetWalletId}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertNotFound();
    }

    public function testMoveNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $foreignWallet = $this->walletFactory->create();
        $foreignTargetWallet = $this->walletFactory->create();

        $wallet = $this->walletFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(10);

        $response = $this->withAuth($auth)->post("/wallets/{$foreignWallet->id}/charges/move/{$foreignTargetWallet->id}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertNotFound();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges/move/{$foreignTargetWallet->id}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertNotFound();
    }

    public function testMoveChangeChargesWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $targetWallet = $this->walletFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(10);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges/move/{$targetWallet->id}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertOk();

        foreach ($charges as $charge) {
            /** @var \App\Database\Charge $charge */
            $this->assertDatabaseMissing('charges', [
                'id' => $charge->id,
                'wallet_id' => $wallet->id,
            ]);

            $this->assertDatabaseHas('charges', [
                'id' => $charge->id,
                'wallet_id' => $targetWallet->id,
            ]);
        }
    }

    public function testMoveThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(10);
        $targetWallet = $this->walletFactory->forUser($user)->create();

        $this->mock(ChargeWalletService::class, ['move'], function (MockObject $mock) {
            $mock->expects($this->once())->method('move')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/charges/move/{$targetWallet->id}", [
            'chargeIds' => $charges->map(fn (Charge $charge) => $charge->id)->toArray(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }
}
