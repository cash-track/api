<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Tags;

use App\Service\TagService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class TagsControllerTest extends TestCase implements DatabaseTransaction
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

    public function testListRequireAuth(): void
    {
        $response = $this->get('/tags');

        $response->assertUnauthorized();
    }

    public function testListReturnTags(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tags = $this->tagFactory->forUser($user)->createMany(10);

        $response = $this->withAuth($auth)->get('/tags');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        foreach ($tags as $tag) {
            $this->assertArrayContains($tag->id, $body, 'data.*.id');
            $this->assertArrayContains($tag->name, $body, 'data.*.name');
        }
    }

    public function testListDoesNotReturnForeignTags(): void
    {
        $this->tagFactory->forUser($this->userFactory->create())->createMany(3);

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/tags');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertEquals([], $body['data']);
    }

    public function testListCommonRequireAuth(): void
    {
        $response = $this->get('/tags/common');

        $response->assertUnauthorized();
    }

    public function testListCommonReturnTags(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        [$tag1, $tag2, $tag3] = $this->tagFactory->forUser($user)->createMany(3)->toArray();

        $wallet = $this->walletFactory->forUser($user)->create();

        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tag1])->createMany(3);
        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tag2])->create();

        $response = $this->withAuth($auth)->get('/tags/common');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(3, $body['data']);

        foreach ([$tag1, $tag2, $tag3] as $index => $tag) {
            $this->assertArrayHasKey($index, $body['data']);
            $this->assertArrayHasKey('id', $body['data'][$index]);
            $this->assertEquals($tag->id, $body['data'][$index]['id']);
            $this->assertArrayHasKey('name', $body['data'][$index]);
            $this->assertEquals($tag->name, $body['data'][$index]['name']);
        }
    }

    public function testListCommonReturnCommonTagsOrderedByChargesCount(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $friend = $this->userFactory->create();

        [$tag1, $tag2, $tag3] = $this->tagFactory->forUser($user)->createMany(3)->toArray();
        $commonTag = $this->tagFactory->forUser($user)->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($user);
        $wallet->users->add($friend);
        $wallet = $this->walletFactory->create($wallet);

        $this->chargeFactory->forWallet($wallet)->forUser($friend)->withTags([$commonTag])->createMany(2);
        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tag1])->createMany(3);
        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tag2])->create();

        $response = $this->withAuth($auth)->get('/tags/common');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(4, $body['data']);

        foreach ([$tag1, $commonTag, $tag2, $tag3] as $index => $tag) {
            $this->assertArrayHasKey($index, $body['data']);
            $this->assertArrayHasKey('id', $body['data'][$index]);
            $this->assertEquals($tag->id, $body['data'][$index]['id']);
            $this->assertArrayHasKey('name', $body['data'][$index]);
            $this->assertEquals($tag->name, $body['data'][$index]['name']);
        }
    }

    public function testCreateRequireAuth(): void
    {
        $response = $this->post('/tags');

        $response->assertUnauthorized();
    }

    public function createValidationFailsDataProvider(): array
    {
        return [
            [[], ['name']],
            [[
                'name' => 1,
                'icon' => 2,
                'color' => 3
            ], ['name', 'icon', 'color']],
            [[
                'name' => 't',
                'icon' => '12345678',
                'color' => '#123abcg'
            ], ['name', 'icon', 'color']],
            [[
                'name' => 'test tag',
                'icon' => '12',
                'color' => '#123abcg'
            ], ['name', 'color']],
            [[
                'name' => Fixtures::string(256)
            ], ['name']],
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
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/tags', $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testCreateValidationFailsDueToUniqueName(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->post('/tags', [
            'name' => $tag->name
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('name', $body['errors']);
    }

    public function testCreateStoreTag(): void
    {
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->post('/tags', [
            'name' => $tag->name,
            'icon' => $tag->icon,
            'color' => $tag->color,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($tag->name, $body, 'data.name');
        $this->assertArrayContains($tag->icon, $body, 'data.icon');
        $this->assertArrayContains($tag->color, $body, 'data.color');
        $this->assertArrayHasKey('id', $body['data']);

        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testCreateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = TagFactory::make();

        $this->mock(TagService::class, ['create'], function (MockObject $mock) {
            $mock->expects($this->once())->method('create')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post('/tags', [
            'name' => $tag->name,
            'icon' => $tag->icon,
            'color' => $tag->color,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('tags', [
            'name' => $tag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testUpdateRequireAuth(): void
    {
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->put("/tags/{$tag->id}");

        $response->assertUnauthorized();
    }

    public function testUpdateMissingTagStillRequireAuth(): void
    {
        $tagId = Fixtures::integer();

        $response = $this->put("/tags/{$tagId}");

        $response->assertUnauthorized();
    }

    public function testUpdateMissingTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->put("/tags/{$tagId}");

        $response->assertNotFound();
    }

    public function testUpdateForeignTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}");

        $response->assertNotFound();
    }

    public function updateValidationFailsDataProvider(): array
    {
        return [
            [[], ['name']],
            [[
                'name' => 1,
                'icon' => 2,
                'color' => 3
            ], ['name', 'icon', 'color']],
            [[
                'name' => 't',
                'icon' => '12345678',
                'color' => '#123abcg'
            ], ['name', 'icon', 'color']],
            [[
                'name' => 'test tag',
                'icon' => '12',
                'color' => '#123abcg'
            ], ['name', 'color']],
            [[
                'name' => Fixtures::string(256)
            ], ['name']],
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

        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testUpdateTagUpdated(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();
        $otherTag = TagFactory::make();

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}", [
            'name' => $otherTag->name,
            'icon' => $otherTag->icon,
            'color' => $otherTag->color,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($otherTag->name, $body, 'data.name');
        $this->assertArrayContains($otherTag->icon, $body, 'data.icon');
        $this->assertArrayContains($otherTag->color, $body, 'data.color');

        $this->assertDatabaseHas('tags', [
            'name' => $otherTag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testUpdateTagWithNoChangesOk(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}", [
            'name' => $tag->name,
            'icon' => $tag->icon,
            'color' => $tag->color,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($tag->name, $body, 'data.name');
        $this->assertArrayContains($tag->icon, $body, 'data.icon');
        $this->assertArrayContains($tag->color, $body, 'data.color');

        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testUpdateTagUsingSameNameOk(): void
    {
        $otherTagName = $this->tagFactory->forUser($otherUser = $this->userFactory->create())->create()->name;

        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();
        $oldTagName = $tag->name;

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}", [
            'name' => $otherTagName,
            'icon' => $tag->icon,
            'color' => $tag->color,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($otherTagName, $body, 'data.name');
        $this->assertArrayContains($tag->icon, $body, 'data.icon');
        $this->assertArrayContains($tag->color, $body, 'data.color');

        $this->assertDatabaseHas('tags', [
            'name' => $otherTagName,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => $otherTagName,
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseMissing('tags', [
            'name' => $oldTagName,
            'user_id' => $user->id,
        ]);
    }

    public function testUpdateThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();
        $otherTag = TagFactory::make();

        $this->mock(TagService::class, ['store'], function (MockObject $mock) {
            $mock->expects($this->once())->method('store')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->put("/tags/{$tag->id}", [
            'name' => $otherTag->name,
            'icon' => $otherTag->icon,
            'color' => $otherTag->color,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('tags', [
            'name' => $otherTag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testDeleteRequireAuth(): void
    {
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->delete("/tags/{$tag->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingTagStillRequireAuth(): void
    {
        $tagId = Fixtures::integer();

        $response = $this->delete("/tags/{$tagId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->delete("/tags/{$tagId}");

        $response->assertNotFound();
    }

    public function testDeleteForeignTagReturnNotFound(): void
    {
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->delete("/tags/{$tag->id}");

        $response->assertNotFound();
    }

    public function testDeleteRemoveTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->delete("/tags/{$tag->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('tags', [
            'name' => $tag->name,
            'user_id' => $user->id,
        ]);
    }

    public function testDeleteThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();

        $this->mock(TagService::class, ['delete'], function (MockObject $mock) {
            $mock->expects($this->once())->method('delete')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->delete("/tags/{$tag->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
            'user_id' => $user->id,
        ]);
    }
}
