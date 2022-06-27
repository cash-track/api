<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Tags;

use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\TagFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class CommonControllerTest extends TestCase implements DatabaseTransaction
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
        $response = $this->get('/tags/common');

        $response->assertUnauthorized();
    }

    public function testListReturnTags(): void
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

    public function testListReturnCommonTagsOrderedByChargesCount(): void
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

    public function testIndexRequireAuth(): void
    {
        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->get("/tags/common/{$tag->id}");

        $response->assertUnauthorized();
    }

    public function testIndexNotFoundStillRequireAuth(): void
    {
        $tagId = Fixtures::integer();

        $response = $this->get("/tags/common/{$tagId}");

        $response->assertUnauthorized();
    }

    public function testIndexMissingTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $tagId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/tags/common/{$tagId}");

        $response->assertNotFound();
    }

    public function testIndexForeignTagReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $tag = $this->tagFactory->forUser($this->userFactory->create())->create();

        $response = $this->withAuth($auth)->get("/tags/common/{$tag->id}");

        $response->assertNotFound();
    }

    public function testIndexReturnOwnTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $tag = $this->tagFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/tags/common/{$tag->id}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($tag->id, $body, 'data.id');
        $this->assertArrayContains($tag->name, $body, 'data.name');
    }

    public function testIndexReturnCommonTag(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $friend = $this->userFactory->create();

        $tag = $this->tagFactory->forUser($friend)->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($friend);
        $this->walletFactory->forUser($user)->create($wallet);

        $response = $this->withAuth($auth)->get("/tags/common/{$tag->id}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($tag->id, $body, 'data.id');
        $this->assertArrayContains($tag->name, $body, 'data.name');
    }
}
