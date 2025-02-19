<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets\Limits;

use App\Service\Limit\LimitService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\LimitFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class LimitsControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected LimitFactory $limitFactory;

    protected TagFactory $tagFactory;

    protected ChargeFactory $chargeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->limitFactory = $this->getContainer()->get(LimitFactory::class);
        $this->tagFactory = $this->getContainer()->get(TagFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
    }

    public function testListRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $this->limitFactory->forWallet($wallet)->create();

        $response = $this->get("/wallets/{$wallet->id}/limits");

        $response->assertUnauthorized();
    }

    public function testListMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/limits");

        $response->assertUnauthorized();
    }

    public function testListMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/limits");

        $response->assertNotFound();
    }

    public function testListForeignWalletReturnNotFound(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $this->limitFactory->forWallet($wallet)->create();

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/limits");

        $response->assertNotFound();
    }

    public function testListNoLimits(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/limits");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertCount(0, $body['data']);
    }

    public function testListReturnLimits(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $this->chargeFactory->forUser($user)->forWallet($wallet)->createMany(4);

        $tag = $this->tagFactory->forUser($user)->create();
        $limit = $this->limitFactory->forWallet($wallet)->withTags([$tag])->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->createMany(4);

        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $limitWithTwoTags = $this->limitFactory->forWallet($wallet)->withTags($tags->toArray())->create();
        $chargesWithTwoTags = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags($tags->toArray())->createMany(4);

        $chargesTotal = 0;
        $chargesCorrectionTotal = 0;
        foreach ($charges as $charge) {
            /** @var \App\Database\Charge $charge */
            if ($limit->type === $charge->type) {
                $chargesTotal += $charge->amount;
            } else {
                $chargesCorrectionTotal += $charge->amount;
            }
        }
        if ($chargesCorrectionTotal >= $chargesTotal) {
            $chargesTotal = 0.0;
        } else {
            $chargesTotal = round($chargesTotal - $chargesCorrectionTotal, 2);
        }

        $chargesWithTwoTagsTotal = 0;
        $chargesWithTwoTagsCorrectionTotal = 0;
        foreach ($chargesWithTwoTags as $charge) {
            if ($limitWithTwoTags->type === $charge->type) {
                $chargesWithTwoTagsTotal += $charge->amount;
            } else {
                $chargesWithTwoTagsCorrectionTotal += $charge->amount;
            }
        }
        if ($chargesWithTwoTagsCorrectionTotal >= $chargesWithTwoTagsTotal) {
            $chargesWithTwoTagsTotal = 0;
        } else {
            $chargesWithTwoTagsTotal = round($chargesWithTwoTagsTotal - $chargesWithTwoTagsCorrectionTotal, 2);
        }

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/limits");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(2, $body['data']);

        foreach ($body['data'] as $limitData) {
            $this->assertArrayHasKey('limit', $limitData);
            $this->assertArrayHasKey('amount', $limitData['limit']);
            if ((string) $limitData['limit']['amount'] === (string) $limit->amount) {
                $this->assertEquals((string) $chargesTotal, (string) ($limitData['amount'] ?? null));
                $this->assertEquals($limit->type, $limitData['limit']['operation'] ?? null);
                $this->assertEquals($limit->amount, $limitData['limit']['amount'] ?? null);
                $this->assertArrayContains($tag->id, $limitData['limit']['tags'], '*.id');
            } else {
                $this->assertEquals((string) $chargesWithTwoTagsTotal, (string) $limitData['amount']);
                $this->assertEquals($limitWithTwoTags->type, $limitData['limit']['operation'] ?? null);
                $this->assertEquals($limitWithTwoTags->amount, $limitData['limit']['amount'] ?? null);
                foreach ($tags as $item) {
                    $this->assertArrayContains($item->id, $limitData['limit']['tags'], '*.id');
                }
            }
        }
    }

    public function testCreateRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $limit = LimitFactory::make();

        $response = $this->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => null,
        ]);

        $response->assertUnauthorized();
    }

    public function testCreateMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $limit = LimitFactory::make();

        $response = $this->post("/wallets/{$walletId}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => null,
        ]);

        $response->assertUnauthorized();
    }

    public function testCreateMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $walletId = Fixtures::integer();
        $tag = $this->tagFactory->forUser($user)->create();

        $limit = LimitFactory::make();

        $response = $this->withAuth($auth)->post("/wallets/{$walletId}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertNotFound();
    }

    public function testCreateNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $limit = LimitFactory::make();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertNotFound();
    }

    public function createValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'tags']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'tags' => false,
            ], ['type', 'amount', 'tags',]],
            [[
                'type' => '+',
                'amount' => 0,
                'tags' => [],
            ], ['amount', 'tags']],
            [[
                'type' => '+',
                'amount' => -1,
                'tags' => [-1],
            ], ['amount', 'tags']],
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

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testCreateStoreLimit(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $limit = LimitFactory::make();
        $tags = $this->tagFactory->forUser($user)->createMany(2);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => $tags->map(fn($tag) => $tag->id)->toArray(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayContains($limit->type, $body, 'data.operation');
        $this->assertArrayContains($limit->amount, $body, 'data.amount');

        $this->assertDatabaseHas('limits', [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'wallet_id' => $wallet->id,
        ]);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('tag_limits', [
                'tag_id' => $tag->id,
                'limit_id' => $body['data']['id'],
            ]);
        }
    }

    public function testCreateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $limit = LimitFactory::make();
        $tags = $this->tagFactory->forUser($user)->createMany(2);

        $this->mock(LimitService::class, ['store'], function (MockObject $mock) {
            $mock->expects($this->once())->method('store')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => $tags->map(fn($tag) => $tag->id)->toArray(),
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
        $limit = $this->limitFactory->forWallet($wallet)->create();

        $updatedLimit = LimitFactory::make();

        $response = $this->put("/wallets/{$wallet->id}/limits/{$limit->id}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletAndLimitStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $limitId = Fixtures::integer();

        $updatedLimit = LimitFactory::make();

        $response = $this->put("/wallets/{$walletId}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingLimitStillRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();
        $limitId = Fixtures::string();

        $updatedLimit = LimitFactory::make();

        $response = $this->put("/wallets/{$wallet->id}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
        ]);

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletAndLimitReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $walletId = Fixtures::integer();
        $limitId = Fixtures::string();
        $tag = $this->tagFactory->forUser($user)->create();

        $updatedLimit = LimitFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$walletId}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertNotFound();
    }

    public function testUpdateMissingLimitReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $limitId = Fixtures::string();
        $tag = $this->tagFactory->forUser($user)->create();

        $updatedLimit = LimitFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertNotFound();
    }

    public function testUpdateNonMemberReturnNotFound(): void
    {
        $wallet = $this->walletFactory->forUser($foreign = $this->userFactory->create())->create();
        $limit = $this->limitFactory->forWallet($wallet)->create();
        $tag = $this->tagFactory->forUser($foreign)->create();
        $auth = $this->makeAuth($this->userFactory->create());

        $updatedLimit = LimitFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limit->id}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertNotFound();
    }

    public function updateValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'tags']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'tags' => '',
            ], ['type', 'amount', 'tags']],
            [[
                'type' => '+',
                'amount' => 0,
                'tags' => [],
            ], ['amount', 'tags']],
            [[
                'type' => '+',
                'amount' => -1,
                'tags' => [-1],
            ], ['amount', 'tags']],
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
        $limit = $this->limitFactory->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limit->id}", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testUpdateStoreLimit(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $limit = LimitFactory::make();
        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertOk();

        $limitId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $updatedLimit = LimitFactory::make();
        $updatedTag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tags' => [$updatedTag->id],
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);


        $this->assertArrayContains($updatedLimit->type, $body, 'data.operation');
        $this->assertArrayContains($updatedLimit->amount, $body, 'data.amount');
        $this->assertArrayContains($updatedTag->id, $body, 'data.tags.*.id');

        $this->assertDatabaseHas('limits', [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertDatabaseHas('tag_limits', [
            'tag_id' => $updatedTag->id,
            'limit_id' => $limitId,
        ]);
    }

    public function testUpdateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $limit = $this->limitFactory->forWallet($wallet)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $updatedLimit = LimitFactory::make();

        $this->mock(LimitService::class, ['store'], function (MockObject $mock) {
            $mock->expects($this->once())->method('store')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limit->id}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tags' => [$tag->id],
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
        $limit = $this->limitFactory->forWallet($wallet)->create();

        $response = $this->delete("/wallets/{$wallet->id}/limits/{$limit->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletAndLimitStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $limitId = Fixtures::string();

        $response = $this->delete("/wallets/{$walletId}/limits/{$limitId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingLimitStillRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();
        $limitId = Fixtures::string();

        $response = $this->delete("/wallets/{$wallet->id}/limits/{$limitId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletAndLimitReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();
        $limitId = Fixtures::string();

        $response = $this->withAuth($auth)->delete("/wallets/{$walletId}/limits/{$limitId}");

        $response->assertNotFound();
    }

    public function testDeleteMissingLimitReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $limitId = Fixtures::string();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/limits/{$limitId}");

        $response->assertNotFound();
    }

    public function testDeleteNonMemberReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $limit = $this->limitFactory->forWallet($wallet)->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/limits/{$limit->id}");

        $response->assertNotFound();
    }

    public function testDeleteRemoveLimit(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $tag = $this->tagFactory->forUser($user)->create();

        $limit = LimitFactory::make();
        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tags' => [$tag->id],
        ]);

        $response->assertOk();

        $limitId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/limits/{$limitId}");

        $response->assertOk();

        $this->assertDatabaseMissing('limits', [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertDatabaseMissing('tag_limits', [
            'tag_id' => $tag->id,
            'limit_id' => $limitId,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
        ]);
    }

    public function testDeleteThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();
        $limit = $this->limitFactory->forWallet($wallet)->create();

        $this->mock(LimitService::class, ['delete'], function (MockObject $mock) {
            $mock->expects($this->once())->method('delete')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/limits/{$limit->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    public function testCopyRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $this->limitFactory->forWallet($sourceWallet)->create();

        $response = $this->post("/wallets/{$wallet->id}/limits/copy/{$sourceWallet->id}");

        $response->assertUnauthorized();
    }

    public function testCopyMissingWalletsStillRequireAuth(): void
    {
        $user = $this->userFactory->create();
        $wallet = $this->walletFactory->forUser($user)->create();
        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $this->limitFactory->forWallet($sourceWallet)->create();

        $walletId = Fixtures::integer();
        $sourceWalletId = Fixtures::integer();

        $response = $this->post("/wallets/{$walletId}/limits/copy/{$sourceWallet->id}");
        $response->assertUnauthorized();

        $response = $this->post("/wallets/{$wallet->id}/limits/copy/{$sourceWalletId}");
        $response->assertUnauthorized();

        $response = $this->post("/wallets/{$walletId}/limits/copy/{$sourceWalletId}");
        $response->assertUnauthorized();
    }

    public function testCopyNonMemberWalletReturnNotFound(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $auth = $this->makeAuth($user = $this->userFactory->create());
        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $this->limitFactory->forWallet($sourceWallet)->create();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits/copy/{$sourceWallet->id}");
        $response->assertNotFound();
    }

    public function testCopyNonMemberSourceWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $sourceWallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $this->limitFactory->forWallet($sourceWallet)->create();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits/copy/{$sourceWallet->id}");
        $response->assertNotFound();
    }

    public function testCopyCreatesLimitsFromSourceWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $limits = $this->limitFactory->forWallet($sourceWallet)->withTags($tags->toArray())->createMany(2);

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits/copy/{$sourceWallet->id}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(count($limits), $body['data']);

        foreach ($limits as $i => $limit) {
            /** @var \App\Database\Limit $limit */
            $this->assertArrayContains($limit->type, $body, 'data.*.limit.operation');
            $this->assertArrayContains($limit->amount, $body, 'data.*.limit.amount');
            $this->assertArrayContains($wallet->id, $body, 'data.*.limit.walletId');

            $this->assertArrayHasKey('tags', $body['data'][$i]['limit']);
            $this->assertCount(count($limits), $body['data'][$i]['limit']['tags']);
            foreach ($tags as $tag) {
                $this->assertArrayContains($tag->id, $body['data'][$i], 'limit.tags.*.id');
            }
        }
    }

    public function testCopyThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $this->limitFactory->forWallet($sourceWallet)->withTags($tags->toArray())->createMany(2);

        $this->mock(LimitService::class, ['copy'], function (MockObject $mock) {
            $mock->expects($this->once())->method('copy')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits/copy/{$sourceWallet->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }
}
