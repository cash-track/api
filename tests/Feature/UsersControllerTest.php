<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class UsersControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
    }

    public function testFindByEmailRequireAuth(): void
    {
        $email = Fixtures::email();

        $response = $this->get("/users/find/by-email/{$email}");

        $response->assertUnauthorized();
    }

    public function testFindByEmailReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $email = Fixtures::email();

        $response = $this->withAuth($auth)->get("/users/find/by-email/{$email}");

        $response->assertNotFound();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(null, $body, 'data');
    }

    public function testFindByEmailFoundYourselfReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->get("/users/find/by-email/{$user->email}");

        $response->assertNotFound();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(null, $body, 'data');
    }

    public function testFindByEmailFoundUser(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $user = $this->userFactory->create();

        $response = $this->withAuth($auth)->get("/users/find/by-email/{$user->email}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($user->id, $body, 'data.id');
        $this->assertArrayContains($user->email, $body, 'data.email');
        $this->assertArrayContains($user->name, $body, 'data.name');
    }

    public function testFindByCommonWalletsRequireAuth(): void
    {
        $response = $this->get('/users/find/by-common-wallets');

        $response->assertUnauthorized();
    }

    public function testFindByCommonWalletsFoundUsersWithCommonWallets(): void
    {
        $auth = $this->makeAuth($Jack = $this->userFactory->create());

        $Daniel = $this->userFactory->create();
        $Samanta = $this->userFactory->create();
        $Murray = $this->userFactory->create();

        $walletOne = WalletFactory::make();
        $walletOne->users->add($Jack);
        $walletOne->users->add($Daniel);
        $this->walletFactory->create($walletOne);

        $walletTwo = WalletFactory::make();
        $walletTwo->users->add($Jack);
        $walletTwo->users->add($Samanta);
        $this->walletFactory->create($walletTwo);

        $response = $this->withAuth($auth)->get('/users/find/by-common-wallets');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($Daniel->id, $body, 'data.*.id');
        $this->assertArrayContains($Daniel->name, $body, 'data.*.name');
        $this->assertArrayContains($Daniel->email, $body, 'data.*.email');

        $this->assertArrayContains($Samanta->id, $body, 'data.*.id');
        $this->assertArrayContains($Samanta->name, $body, 'data.*.name');
        $this->assertArrayContains($Samanta->email, $body, 'data.*.email');

        $this->assertArrayNotContains($Murray->id, $body, 'data.*.id');
        $this->assertArrayNotContains($Murray->name, $body, 'data.*.name');
        $this->assertArrayNotContains($Murray->email, $body, 'data.*.email');
    }
}
