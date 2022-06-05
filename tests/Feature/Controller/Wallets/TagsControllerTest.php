<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

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
}
