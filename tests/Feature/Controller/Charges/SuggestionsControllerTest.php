<?php

declare(strict_types=1);

namespace Feature\Controller\Charges;

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
    }

    public function testSuggestionsRequireAuth()
    {
        $query = Fixtures::string();

        $response = $this->get("/charges/title/suggestions/{$query}");

        $response->assertUnauthorized();
    }

    public function testSuggestionsReturnSuggestedTitles()
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $sharedUser = $this->userFactory->create();
        $otherUser = $this->userFactory->create();

        $otherWallet = $this->walletFactory->forUser($otherUser)->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($sharedUser);
        $wallet = $this->walletFactory->forUser($user)->create($wallet);

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

        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser1);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser2);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser3);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser4);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser5);
        $this->chargeFactory->forUser($user)->forWallet($wallet)->create($chargeUser6);
        $this->chargeFactory->forUser($sharedUser)->forWallet($wallet)->create($chargeSharedUser);
        $this->chargeFactory->forUser($otherUser)->forWallet($otherWallet)->create($chargeOtherUser);

        $response = $this->withAuth($auth)->get("/charges/title/suggestions/{$query}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertIsArray($body);
        $this->assertArrayHasKey('data', $body);

        $this->assertCount(6, $body['data']);

        foreach ([$chargeSharedUser, $chargeUser1, $chargeUser2, $chargeUser3, $chargeUser5, $chargeUser4] as $charge) {
            $this->assertArrayContains($charge->title, $body, 'data.*.title');
        }

        foreach ([$chargeUser6, $chargeOtherUser] as $charge) {
            $this->assertArrayNotContains($charge->title, $body, 'data.*.title');
        }
    }
}
