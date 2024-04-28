<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Database\Charge;
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
        $wallet = $this->walletFactory->create();

        $response = $this->get("/wallets/{$wallet->id}/tags");

        $response->assertUnauthorized();
    }

    public function testListOfMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/tags");

        $response->assertUnauthorized();
    }

    public function testListOfMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/tags");

        $response->assertNotFound();
    }

    public function testListOfWalletForNonMemberNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/tags");

        $response->assertNotFound();
    }

    public function testListWalletTags(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        [$tag1, $tag2, $tag3] = $this->tagFactory->forUser($user)->createMany(3)->getValues();

        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag1])->createMany(3);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag2])->createMany(2);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/tags");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertCount(2, $body['data']);

        foreach ([$tag1, $tag2] as $index => $tag) {
            $this->assertIsArray($body['data'][$index]);
            $this->assertArrayHasKey('id', $body['data'][$index]);
            $this->assertEquals($tag->id, $body['data'][$index]['id']);
            $this->assertArrayHasKey('name', $body['data'][$index]);
            $this->assertEquals($tag->name, $body['data'][$index]['name']);
        }

        $this->assertArrayNotContains($tag3->id, $body, 'data.*.id');
        $this->assertArrayNotContains($tag3->name, $body, 'data.*.name');
    }

    public function testFindRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $query = Fixtures::string();

        $response = $this->get("/wallets/{$wallet->id}/tags/find/{$query}");

        $response->assertUnauthorized();
    }

    public function testFindOfMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $query = Fixtures::string();

        $response = $this->get("/wallets/{$walletId}/tags/find/{$query}");

        $response->assertUnauthorized();
    }

    public function testFindOfMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $walletId = Fixtures::integer();
        $query = Fixtures::string();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/tags/find/{$query}");

        $response->assertNotFound();
    }

    public function testFindOfForeignWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();
        $query = Fixtures::string();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/tags/find/{$query}");

        $response->assertNotFound();
    }

    public function testFindSearchTags(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $sharedUser = $this->userFactory->create();
        $otherUser = $this->userFactory->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($sharedUser);
        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        $tagUser1 = TagFactory::make();
        $tagUser2 = TagFactory::make();
        $tagUser3 = TagFactory::make();
        $tagUser4 = TagFactory::make();
        $tagSharedUser1 = TagFactory::make();
        $tagOtherUser1 = TagFactory::make();

        $tagUser1->name = 'Tag-name-1';
        $tagUser2->name = 'Tag-name-2';
        $tagUser3->name = 'Other-Tag-name-3';
        $tagUser4->name = 'Tag-name-4-without-charge';
        $tagSharedUser1->name = 'Tag-name-shared-1';
        $tagOtherUser1->name = 'Tag-name-other-1';
        $query = 'Tag-';

        $tagUser1 = $this->tagFactory->forUser($user)->create($tagUser1);
        $tagUser2 = $this->tagFactory->forUser($user)->create($tagUser2);
        $tagUser3 = $this->tagFactory->forUser($user)->create($tagUser3);
        $tagUser4 = $this->tagFactory->forUser($user)->create($tagUser4);
        $tagSharedUser1 = $this->tagFactory->forUser($sharedUser)->create($tagSharedUser1);
        $tagOtherUser1 = $this->tagFactory->forUser($otherUser)->create($tagOtherUser1);

        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tagUser1])->createMany(2);
        $this->chargeFactory->forWallet($wallet)->forUser($user)->withTags([$tagUser2])->createMany(3);
        $this->chargeFactory->forWallet($wallet)->forUser($sharedUser)->withTags([$tagSharedUser1])->createMany(1);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/tags/find/{$query}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(4, $body['data']);

        foreach ([$tagUser2, $tagUser1, $tagUser4, $tagSharedUser1] as $index => $tag) {
            $this->assertArrayHasKey($index, $body['data']);
            $this->assertArrayHasKey('id', $body['data'][$index]);
            $this->assertEquals($tag->id, $body['data'][$index]['id']);
            $this->assertArrayHasKey('name', $body['data'][$index]);
            $this->assertEquals($tag->name, $body['data'][$index]['name']);
        }

        foreach ([$tagUser3, $tagOtherUser1] as $tag) {
            $this->assertArrayNotContains($tag->id, $body, 'data.*.id');
            $this->assertArrayNotContains($tag->name, $body, 'data.*.name');
        }
    }
}
