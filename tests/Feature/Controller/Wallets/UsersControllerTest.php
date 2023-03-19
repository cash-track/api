<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Service\Mailer\MailerInterface;
use App\Service\WalletService;
use PHPUnit\Framework\MockObject\MockObject;
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

    public function testUsersRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->get("/wallets/{$wallet->id}/users");

        $response->assertUnauthorized();
    }

    public function testUsersNotExistingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/users");

        $response->assertUnauthorized();
    }

    public function testUsersNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/users");

        $response->assertNotFound();
    }

    public function testUsersReturnNotFoundForNonMembers(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/users");

        $response->assertNotFound();
    }

    public function testUsersReturnWalletMembers(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $otherUser = $this->userFactory->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($user);
        $wallet->users->add($otherUser);

        $this->walletFactory->create($wallet);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/users");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($user->id, $body, 'data.*.id');
        $this->assertArrayContains($user->name, $body, 'data.*.name');
        $this->assertArrayContains($otherUser->id, $body, 'data.*.id');
        $this->assertArrayContains($otherUser->name, $body, 'data.*.name');
    }

    public function testPathRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $otherUser = $this->userFactory->create();

        $response = $this->patch("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertUnauthorized();
    }

    public function testPatchNotExistingWalletAndUserStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $userId = Fixtures::integer();

        $response = $this->patch("/wallets/{$walletId}/users/{$userId}");

        $response->assertUnauthorized();
    }

    public function testPatchNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();
        $user = $this->userFactory->create();

        $response = $this->withAuth($auth)->patch("/wallets/{$walletId}/users/{$user->id}");

        $response->assertNotFound();
    }

    public function testPatchNotExistingUserReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherUserId = Fixtures::integer(100, 1000);

        $response = $this->withAuth($auth)->patch("/wallets/{$wallet->id}/users/{$otherUserId}");

        $response->assertNotFound();
    }

    public function testPatchReturnNotFoundForNonMembers(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $otherUser = $this->userFactory->create();

        $response = $this->withAuth($auth)->patch("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertNotFound();
    }

    public function testPatchAddNewMember(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $otherUser = $this->userFactory->create();

        $this->mock(MailerInterface::class, ['send', 'render'], function (MockObject $mock) {
            $mock->expects($this->once())->method('send');
        });

        $response = $this->withAuth($auth)->patch("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertOk();

        $this->assertDatabaseHas('user_wallets', [
            'wallet_id' => $wallet->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testPatchThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $otherUser = $this->userFactory->create();

        $this->mock(WalletService::class, ['share'], function (MockObject $mock) {
            $mock->expects($this->once())->method('share')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->patch("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertStatus(500);

        $this->assertDatabaseMissing('user_wallets', [
            'wallet_id' => $wallet->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testDeleteRequireAuth(): void
    {
        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $otherUser = $this->userFactory->create();

        $response = $this->delete("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteNotExistingWalletAndUserStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();
        $userId = Fixtures::integer();

        $response = $this->delete("/wallets/{$walletId}/users/{$userId}");

        $response->assertUnauthorized();
    }

    public function testDeleteNotExistingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();
        $user = $this->userFactory->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$walletId}/users/{$user->id}");

        $response->assertNotFound();
    }

    public function testDeleteNotExistingUserReturnNotFound(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherUserId = Fixtures::integer(100, 1000);

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$otherUserId}");

        $response->assertNotFound();
    }

    public function testDeleteReturnNotFoundForNonMembers(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->forUser($this->userFactory->create())->create();

        $otherUser = $this->userFactory->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertNotFound();
    }

    public function testDeleteReturnOkForAlreadyNonMembers(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $otherUser = $this->userFactory->create();

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertOk();
    }

    public function testDeleteDoesNotRemoveLastMember(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$user->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('user_wallets', [
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
        ]);
    }

    public function testDeleteRemoveMember(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $otherUser = $this->userFactory->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($user);
        $wallet->users->add($otherUser);
        $wallet = $this->walletFactory->create($wallet);

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('user_wallets', [
            'wallet_id' => $wallet->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function testDeleteThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $otherUser = $this->userFactory->create();

        $wallet = WalletFactory::make();
        $wallet->users->add($user);
        $wallet->users->add($otherUser);
        $wallet = $this->walletFactory->create($wallet);

        $this->mock(WalletService::class, ['revoke'], function (MockObject $mock) {
            $mock->expects($this->once())->method('revoke')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}/users/{$otherUser->id}");

        $response->assertStatus(500);

        $this->assertDatabaseHas('user_wallets', [
            'wallet_id' => $wallet->id,
            'user_id' => $otherUser->id,
        ]);
    }
}
