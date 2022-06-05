<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Database\Wallet;
use App\Repository\UserRepository;
use App\Service\Sort\SortService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class ListControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
    }

    public function testListRequireAuth(): void
    {
        $response = $this->get('/wallets');

        $response->assertUnauthorized();
    }

    public function testListReturnWallets(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        /** @var \Doctrine\Common\Collections\ArrayCollection<int, Wallet> $wallets */
        $wallets = $this->walletFactory->forUser($user)->createMany(3);

        $response = $this->withAuth($auth)->get('/wallets');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        foreach ($wallets as $wallet) {
            $this->assertArrayContains($wallet->id, $body, 'data.*.id');
            $this->assertArrayContains($wallet->name, $body, 'data.*.name');
            $this->assertArrayContains($wallet->slug, $body, 'data.*.slug');
        }
    }

    public function testListArchivedRequireAuth(): void
    {
        $response = $this->get('/wallets/archived');

        $response->assertUnauthorized();
    }

    public function testListArchivedReturnArchivedWallets(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $unArchived = $this->walletFactory->forUser($user)->create();
        $archived = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $response = $this->withAuth($auth)->get('/wallets/archived');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($archived->id, $body, 'data.*.id');
        $this->assertArrayContains($archived->name, $body, 'data.*.name');
        $this->assertArrayContains($archived->slug, $body, 'data.*.slug');

        $this->assertArrayNotContains($unArchived->id, $body, 'data.*.id');
        $this->assertArrayNotContains($unArchived->name, $body, 'data.*.name');
        $this->assertArrayNotContains($unArchived->slug, $body, 'data.*.slug');
    }

    public function testListUnArchivedRequireAuth(): void
    {
        $response = $this->get('/wallets/unarchived');

        $response->assertUnauthorized();
    }

    public function testListUnArchivedReturnUnArchivedWallets(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $unArchived = $this->walletFactory->forUser($user)->create();
        $archived = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $response = $this->withAuth($auth)->get('/wallets/unarchived');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($unArchived->id, $body, 'data.*.id');
        $this->assertArrayContains($unArchived->name, $body, 'data.*.name');
        $this->assertArrayContains($unArchived->slug, $body, 'data.*.slug');

        $this->assertArrayNotContains($archived->id, $body, 'data.*.id');
        $this->assertArrayNotContains($archived->name, $body, 'data.*.name');
        $this->assertArrayNotContains($archived->slug, $body, 'data.*.slug');
    }

    public function testListUnArchivedWithSortReturnSortedUnArchivedWallets(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallets = $this->walletFactory->forUser($user)->createMany(3);
        $wallet = $wallets->first();

        $order = $wallets->map(fn ($wallet) => $wallet->id)->toArray();
        shuffle($order);

        $missed = array_pop($order);

        $response = $this->withAuth($auth)->post('/wallets/unarchived/sort', [
            'sort' => $order,
        ]);

        $response->assertOk();

        $archived = $this->walletFactory->forUser($user)->create(WalletFactory::archived());

        $response = $this->withAuth($auth)->get('/wallets/unarchived');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($wallet->id, $body, 'data.*.id');
        $this->assertArrayContains($wallet->name, $body, 'data.*.name');
        $this->assertArrayContains($wallet->slug, $body, 'data.*.slug');

        $this->assertArrayNotContains($archived->id, $body, 'data.*.id');
        $this->assertArrayNotContains($archived->name, $body, 'data.*.name');
        $this->assertArrayNotContains($archived->slug, $body, 'data.*.slug');

        $ids = array_map(fn($item) => $item['id'] ?? 0 , $body['data']);

        // make sure unordered wallets get's prepended to the order
        array_unshift($order, $missed);

        $this->assertEquals($order, $ids);
    }

    public function testSortUnArchivedRequireAuth(): void
    {
        $response = $this->post('/wallets/unarchived/sort');

        $response->assertUnauthorized();
    }

    public function testSortUnArchivedValidationFailsDueToWalletDoesNotExists(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallets = $this->walletFactory->forUser($user)->createMany(3);

        $walletIDs = $wallets->map(fn($wallet) => $wallet->id)->toArray();

        array_push($walletIDs, 9999);

        $response = $this->withAuth($auth)->post('/wallets/unarchived/sort', [
            'sort' => $walletIDs,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
    }

    public function testSortUnArchivedUpdateOptions(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallets = $this->walletFactory->forUser($user)->createMany(3);

        $walletIDs = $wallets->map(fn($wallet) => $wallet->id)->toArray();

        $response = $this->withAuth($auth)->post('/wallets/unarchived/sort', [
            'sort' => $walletIDs,
        ]);

        $response->assertOk();

        $user = $this->getContainer()->get(UserRepository::class)->findByPK($user->id);

        $this->assertEquals($walletIDs, $user->options['sort']['wallet'] ?? []);
    }

    public function testSortUnArchivedThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallets = $this->walletFactory->forUser($user)->createMany(3);

        $walletIDs = $wallets->map(fn($wallet) => $wallet->id)->toArray();

        $this->mock(SortService::class, ['set'], function (MockObject $mock) {
            $mock->expects($this->once())->method('set')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post('/wallets/unarchived/sort', [
            'sort' => $walletIDs,
        ]);

        $response->assertOk();
    }
}
