<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets\Limits;

use App\Database\LimitTagGroup;
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

    /**
     * Fetches the `limit_tag_group_id` of the (only) group created for a limit and
     * asserts the pivot row linking it to $tagId exists.
     */
    protected function assertTagGroupHasTag(int $limitId, int $tagId): void
    {
        $groups = $this->queryDatabase('limit_tag_groups', ['limit_id' => $limitId]);

        $this->assertNotEmpty($groups, "No limit_tag_groups row found for limit {$limitId}");

        $groupIds = array_column($groups, 'id');

        $this->assertDatabaseHas('tag_limit_tag_groups', [
            'limit_tag_group_id' => ['in' => $groupIds],
            'tag_id' => $tagId,
        ]);
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
        $limit = $this->limitFactory->forWallet($wallet)->withTagGroups([
            ['connection' => LimitTagGroup::CONNECTION_AND, 'tags' => [$tag]],
        ])->create();
        $charges = $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag])->createMany(4);

        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $limitWithTwoTags = $this->limitFactory->forWallet($wallet)->withTagGroups([
            ['connection' => LimitTagGroup::CONNECTION_AND, 'tags' => $tags->toArray()],
        ])->create();
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
            $this->assertEquals([], $limitData['limit']['tags']);

            if ((string) $limitData['limit']['amount'] === (string) $limit->amount) {
                $this->assertEquals((string) $chargesTotal, (string) ($limitData['amount'] ?? null));
                $this->assertEquals($limit->type, $limitData['limit']['operation'] ?? null);
                $this->assertEquals($limit->amount, $limitData['limit']['amount'] ?? null);
                $this->assertArrayContains($tag->id, $limitData['limit']['tagGroups'], '*.tags.*.id');
            } else {
                $this->assertEquals((string) $chargesWithTwoTagsTotal, (string) $limitData['amount']);
                $this->assertEquals($limitWithTwoTags->type, $limitData['limit']['operation'] ?? null);
                $this->assertEquals($limitWithTwoTags->amount, $limitData['limit']['amount'] ?? null);
                foreach ($tags as $item) {
                    $this->assertArrayContains($item->id, $limitData['limit']['tagGroups'], '*.tags.*.id');
                }
            }
        }
    }

    public function testListReturnLimitsSumsAcrossGroupsWithoutDedup(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $tagA = $this->tagFactory->forUser($user)->create();
        $tagB = $this->tagFactory->forUser($user)->create();

        // Charge matches both group A ([tagA], AND) and group B ([tagB], OR).
        $charge = ChargeFactory::expense();
        $charge->amount = 42.5;
        $sharedCharge = $this->chargeFactory->forUser($user)->forWallet($wallet)
            ->withTags([$tagA, $tagB])
            ->create($charge);

        $this->limitFactory->forWallet($wallet)
            ->withTagGroups([
                ['connection' => LimitTagGroup::CONNECTION_AND, 'tags' => [$tagA]],
                ['connection' => LimitTagGroup::CONNECTION_OR, 'tags' => [$tagB]],
            ])
            ->create(LimitFactory::expense());

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/limits");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertCount(1, $body['data']);

        // The shared charge matches both groups, so it must be counted twice (no dedup).
        $expected = round($sharedCharge->amount * 2, 2);
        $this->assertEquals((string) $expected, (string) $body['data'][0]['amount']);
    }

    public function testCreateRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $limit = LimitFactory::make();

        $response = $this->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tagGroups' => null,
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
            'tagGroups' => null,
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
        ]);

        $response->assertNotFound();
    }

    public function createValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'tagGroups']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'tagGroups' => false,
            ], ['type', 'amount', 'tagGroups']],
            [[
                'type' => '+',
                'amount' => 0,
                'tagGroups' => [],
            ], ['amount', 'tagGroups']],
            [[
                'type' => '+',
                'amount' => -1,
                'tagGroups' => [['operation' => 'and', 'tags' => [-1]]],
            ], ['amount', 'tagGroups']],
            [[
                'type' => '+',
                'amount' => 1,
                'tagGroups' => [['operation' => 'xor', 'tags' => [-1]]],
            ], ['tagGroups']],
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
            'tagGroups' => [['operation' => 'and', 'tags' => $tags->map(fn($tag) => $tag->id)->toArray()]],
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayContains($limit->type, $body, 'data.operation');
        $this->assertArrayContains($limit->amount, $body, 'data.amount');
        $this->assertEquals([], $body['data']['tags']);
        $this->assertCount(1, $body['data']['tagGroups']);
        $this->assertEquals('and', $body['data']['tagGroups'][0]['connection']);

        $this->assertDatabaseHas('limits', [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertDatabaseHas('limit_tag_groups', [
            'limit_id' => $body['data']['id'],
            'connection' => 'and',
        ]);

        foreach ($tags as $tag) {
            $this->assertTagGroupHasTag((int) $body['data']['id'], (int) $tag->id);
        }
    }

    public function testCreateStoreLimitWithMultipleTagGroups(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $limit = LimitFactory::make();
        $tagA = $this->tagFactory->forUser($user)->create();
        $tagB = $this->tagFactory->forUser($user)->create();
        $tagC = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->post("/wallets/{$wallet->id}/limits", [
            'type' => $limit->type,
            'amount' => $limit->amount,
            'tagGroups' => [
                ['operation' => 'and', 'tags' => [$tagA->id]],
                ['operation' => 'or', 'tags' => [$tagB->id, $tagC->id]],
            ],
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertCount(2, $body['data']['tagGroups']);

        $connections = array_map(fn($group) => $group['connection'], $body['data']['tagGroups']);
        sort($connections);
        $this->assertEquals(['and', 'or'], $connections);

        $this->assertArrayContains($tagA->id, $body['data']['tagGroups'], '*.tags.*.id');
        $this->assertArrayContains($tagB->id, $body['data']['tagGroups'], '*.tags.*.id');
        $this->assertArrayContains($tagC->id, $body['data']['tagGroups'], '*.tags.*.id');

        $this->assertDatabaseCount(2, 'limit_tag_groups', ['limit_id' => $body['data']['id']]);
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
            'tagGroups' => [['operation' => 'and', 'tags' => $tags->map(fn($tag) => $tag->id)->toArray()]],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
        ]);

        $response->assertNotFound();
    }

    public function updateValidationFailsDataProvider(): array
    {
        return [
            [[], ['type', 'amount', 'tagGroups']],
            [[
                'type' => 'W',
                'amount' => 'false',
                'tagGroups' => '',
            ], ['type', 'amount', 'tagGroups']],
            [[
                'type' => '+',
                'amount' => 0,
                'tagGroups' => [],
            ], ['amount', 'tagGroups']],
            [[
                'type' => '+',
                'amount' => -1,
                'tagGroups' => [['operation' => 'and', 'tags' => [-1]]],
            ], ['amount', 'tagGroups']],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
        ]);

        $response->assertOk();

        $limitId = $this->getJsonResponseBody($response)['data']['id'] ?? null;

        $updatedLimit = LimitFactory::make();
        $updatedTag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}/limits/{$limitId}", [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'tagGroups' => [['operation' => 'or', 'tags' => [$updatedTag->id]]],
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($updatedLimit->type, $body, 'data.operation');
        $this->assertArrayContains($updatedLimit->amount, $body, 'data.amount');
        $this->assertArrayContains($updatedTag->id, $body, 'data.tagGroups.*.tags.*.id');

        $this->assertDatabaseHas('limits', [
            'type' => $updatedLimit->type,
            'amount' => $updatedLimit->amount,
            'wallet_id' => $wallet->id,
        ]);

        $this->assertDatabaseHas('limit_tag_groups', [
            'limit_id' => $limitId,
            'connection' => 'or',
        ]);
        $this->assertTagGroupHasTag((int) $limitId, (int) $updatedTag->id);

        // Old group's tag must no longer be attached to this limit.
        $oldGroups = $this->queryDatabase('limit_tag_groups', ['limit_id' => $limitId]);
        $this->assertCount(1, $oldGroups);
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
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
            'tagGroups' => [['operation' => 'and', 'tags' => [$tag->id]]],
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

        $this->assertDatabaseMissing('limit_tag_groups', [
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
        $limits = $this->limitFactory->forWallet($sourceWallet)->withTagGroups([
            ['connection' => LimitTagGroup::CONNECTION_AND, 'tags' => $tags->toArray()],
        ])->createMany(2);

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

            $this->assertArrayHasKey('tagGroups', $body['data'][$i]['limit']);
            $this->assertCount(1, $body['data'][$i]['limit']['tagGroups']);
            $this->assertCount(count($tags), $body['data'][$i]['limit']['tagGroups'][0]['tags']);
            foreach ($tags as $tag) {
                $this->assertArrayContains($tag->id, $body['data'][$i]['limit']['tagGroups'], '*.tags.*.id');
            }
        }
    }

    public function testCopyThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $sourceWallet = $this->walletFactory->forUser($user)->create();
        $tags = $this->tagFactory->forUser($user)->createMany(2);
        $this->limitFactory->forWallet($sourceWallet)->withTagGroups([
            ['connection' => LimitTagGroup::CONNECTION_AND, 'tags' => $tags->toArray()],
        ])->createMany(2);

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
