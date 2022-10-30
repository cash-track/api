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

class SuggestionsControllerTest extends TestCase implements DatabaseTransaction
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

    public function testSuggestionsRequireAuth()
    {
        $query = Fixtures::string();

        $response = $this->get("/tags/suggestions/{$query}");

        $response->assertUnauthorized();
    }

    public function testSuggestionsReturnSuggestedTags()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $sharedUser = $this->userFactory->create();
        $otherUser = $this->userFactory->create();

        $otherWallet = $this->walletFactory->forUser($otherUser)->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($sharedUser);
        $wallet = $this->walletFactory->forUser($user)->create($wallet);

        [$tag1, $tag2, $tag3] = $this->tagFactory->forUser($user)->createMany(5);
        $sharedTag = $this->tagFactory->forUser($sharedUser)->create();
        $otherTag = $this->tagFactory->forUser($otherUser)->create();

        $chargeUser1 = ChargeFactory::make();
        $chargeUser2 = ChargeFactory::make();
        $chargeUser3 = ChargeFactory::make();
        $chargeUser4 = ChargeFactory::make();
        $chargeUser5 = ChargeFactory::make();
        $chargeUser6 = ChargeFactory::make();
        $chargeSharedUser = ChargeFactory::make();
        $chargeOtherUser = ChargeFactory::make();

        $chargeUser1->title = 'Charge title 1';
        $chargeUser2->title = 'Charge title 2';
        $chargeUser3->title = 'Other charge title 3';
        $chargeUser4->title = 'Other charge title 4';
        $chargeUser5->title = 'Other charge title 5';
        $chargeUser6->title = 'Other item outside 5';
        $chargeSharedUser->title = 'Charge title shared 1';
        $chargeOtherUser->title = 'Charge title other 1';
        $query = 'charge';

        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag1])->create($chargeUser1);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag1])->create($chargeUser2);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag2])->create($chargeUser3);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag2])->create($chargeUser4);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag2])->create($chargeUser5);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->withTags([$tag3])->create($chargeUser6);
        $this->chargeFactory->forUser($sharedUser)->forWallet($wallet)->withTags([$sharedTag])->create($chargeSharedUser);
        $this->chargeFactory->forUser($otherUser)->forWallet($otherWallet)->withTags([$otherTag])->create($chargeOtherUser);

        $response = $this->withAuth($auth)->get("/tags/suggestions/{$query}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);
        $this->assertCount(3, $body['data']);

        foreach ([$tag2, $tag1, $sharedTag] as $index => $tag) {
            $this->assertArrayHasKey($index, $body['data']);
            $this->assertArrayHasKey('id', $body['data'][$index]);
            $this->assertEquals($tag->id, $body['data'][$index]['id']);
            $this->assertArrayHasKey('name', $body['data'][$index]);
            $this->assertEquals($tag->name, $body['data'][$index]['name']);
        }

        foreach ([$tag3, $otherTag] as $tag) {
            $this->assertArrayNotContains($tag->id, $body, 'data.*.id');
            $this->assertArrayNotContains($tag->name, $body, 'data.*.name');
        }
    }
}
