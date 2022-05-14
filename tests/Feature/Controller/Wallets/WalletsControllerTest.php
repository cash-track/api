<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Database\Charge;
use App\Database\Wallet;
use App\Repository\CurrencyRepository;
use App\Repository\UserRepository;
use App\Service\Sort\SortService;
use App\Service\WalletService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class WalletsControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected ChargeFactory $chargeFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
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
        $response = $this->get('/wallets/un-archived');

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

    public function testIndexRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->get("/wallets/{$wallet->id}");

        $response->assertUnauthorized();
    }

    public function testIndexMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}");

        $response->assertUnauthorized();
    }

    public function testIndexMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}");

        $response->assertNotFound();
    }

    public function testIndexWalletForNonMemberNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}");

        $response->assertNotFound();
    }

    public function testIndexWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($wallet->id, $body, 'data.id');
        $this->assertArrayContains($wallet->name, $body, 'data.name');
        $this->assertArrayContains($wallet->slug, $body, 'data.slug');
    }

    public function testIndexTotalRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->get("/wallets/{$wallet->id}/total");

        $response->assertUnauthorized();
    }

    public function testIndexTotalMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->get("/wallets/{$walletId}/total");

        $response->assertUnauthorized();
    }

    public function testIndexTotalMissingWalletNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->get("/wallets/{$walletId}/total");

        $response->assertNotFound();
    }

    public function testIndexTotalWalletForNonMemberNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertNotFound();
    }

    public function testIndexTotalEmptyWalletTotal(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains(0, $body, 'data.totalAmount');
        $this->assertArrayContains(0, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains(0, $body, 'data.totalExpenseAmount');
    }

    public function testIndexTotalWalletTotal(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $this->chargeFactory->forUser($user)->forWallet($wallet);

        $chargesAmount = rand(5, 20);
        $totalIncome = 0.0;
        $totalExpense = 0.0;

        for ($i = 0; $i < $chargesAmount; $i++) {
            $charge = $this->chargeFactory->create();

            if ($charge->type === Charge::TYPE_INCOME) {
                $totalIncome += $charge->amount;
            } else {
                $totalExpense += $charge->amount;
            }
        }

        $wallet->totalAmount = $total = round($totalIncome - $totalExpense, 2);
        $this->walletFactory->persist($wallet);

        $response = $this->withAuth($auth)->get("/wallets/{$wallet->id}/total");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($total, $body, 'data.totalAmount');
        $this->assertArrayContains($totalIncome, $body, 'data.totalIncomeAmount');
        $this->assertArrayContains($totalExpense, $body, 'data.totalExpenseAmount');
    }

    public function testCreateRequireAuth(): void
    {
        $response = $this->post('/wallets');

        $response->assertUnauthorized();
    }

    public function createValidationFailsDataProvider(): array
    {
        return [
            [[], ['name']],
            [[
                'name' => 123,
                'slug' => 123,
                'isPublic' => 'public',
                'defaultCurrencyCode' => 123,
            ], ['name', 'slug', 'isPublic', 'defaultCurrencyCode']],
            [[
                'name' => 'Test',
                'slug' => 'slug-!@#$%^&*()=+-slug',
                'defaultCurrencyCode' => 'WTF',
            ], ['slug', 'defaultCurrencyCode']],
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

        $response = $this->withAuth($auth)->post('/wallets', $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testCreateValidationFailsDueToSlugAlreadyExists(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->post('/wallets', [
            'name' => 'Test',
            'slug' => $wallet->slug
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('slug', $body['errors']);
    }

    public function testCreateStoreWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = WalletFactory::make();

        $response = $this->withAuth($auth)->post('/wallets', [
            'name' => $wallet->name,
            'slug' => $wallet->slug,
            'isPublic' => $wallet->isPublic,
            'defaultCurrencyCode' => $wallet->defaultCurrencyCode,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($wallet->name, $body, 'data.name');
        $this->assertArrayContains($wallet->slug, $body, 'data.slug');
        $this->assertArrayContains($wallet->isPublic, $body, 'data.isPublic');
        $this->assertArrayContains($wallet->defaultCurrencyCode, $body, 'data.defaultCurrencyCode');
        $this->assertArrayHasKey('id', $body['data']);

        $this->assertDatabaseHas('wallets', [
            'name' => $wallet->name,
            'slug' => $wallet->slug,
            'default_currency_code' => $wallet->defaultCurrencyCode,
        ]);

        $this->assertDatabaseHas('user_wallets', [
            'wallet_id' => $body['data']['id'],
            'user_id' => $user->id,
        ]);
    }

    public function testCreateThrownException(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = WalletFactory::make();

        $this->mock(WalletService::class, ['create'], function (MockObject $mock) {
            $mock->expects($this->once())->method('create')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->post('/wallets', [
            'name' => $wallet->name,
            'slug' => $wallet->slug,
            'isPublic' => $wallet->isPublic,
            'defaultCurrencyCode' => $wallet->defaultCurrencyCode,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('wallets', [
            'name' => $wallet->name,
            'slug' => $wallet->slug,
            'default_currency_code' => $wallet->defaultCurrencyCode,
        ]);
    }

    public function testUpdateRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->put("/wallets/{$wallet->id}");

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->put("/wallets/{$walletId}");

        $response->assertUnauthorized();
    }

    public function testUpdateMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->put("/wallets/{$walletId}");

        $response->assertNotFound();
    }

    public function testUpdateNonMemberWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}");

        $response->assertNotFound();
    }

    public function updateValidationFailsDataProvider(): array
    {
        return [
            [[], ['name', 'defaultCurrencyCode']],
            [[
                'name' => 123,
                'isPublic' => 'public',
                'defaultCurrencyCode' => 123,
            ], ['name', 'defaultCurrencyCode']],
            [[
                'name' => 'Test',
                'defaultCurrencyCode' => 'WTF',
            ], ['defaultCurrencyCode']],
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

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}", $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testUpdateWalletUpdated(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = WalletFactory::make();

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}", [
            'name' => $otherWallet->name,
            'isPublic' => $otherWallet->isPublic,
            'defaultCurrencyCode' => $otherWallet->defaultCurrencyCode,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayContains($otherWallet->name, $body, 'data.name');
        $this->assertArrayContains($wallet->slug, $body, 'data.slug');
        $this->assertArrayContains($otherWallet->isPublic, $body, 'data.isPublic');
        $this->assertArrayContains($otherWallet->defaultCurrencyCode, $body, 'data.defaultCurrencyCode');

        $this->assertDatabaseHas('wallets', [
            'name' => $otherWallet->name,
            'slug' => $wallet->slug,
            'is_public' => $otherWallet->isPublic,
            'default_currency_code' => $otherWallet->defaultCurrencyCode,
        ]);
    }

    public function testUpdateMissingDefaultCurrency(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = WalletFactory::make();

        $this->mock(CurrencyRepository::class, ['findByPK'], function (MockObject $mock) {
            $mock->expects($this->once())->method('findByPK')->willReturn(null);
        });

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}", [
            'name' => $otherWallet->name,
            'isPublic' => $otherWallet->isPublic,
            'defaultCurrencyCode' => $otherWallet->defaultCurrencyCode,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('wallets', [
            'name' => $otherWallet->name,
            'slug' => $wallet->slug,
            'is_public' => $otherWallet->isPublic,
            'default_currency_code' => $otherWallet->defaultCurrencyCode,
        ]);
    }

    public function testUpdateCurrencyRepositoryThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = WalletFactory::make();

        $this->mock(CurrencyRepository::class, ['findByPK'], function (MockObject $mock) {
            $mock->expects($this->once())->method('findByPK')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}", [
            'name' => $otherWallet->name,
            'isPublic' => $otherWallet->isPublic,
            'defaultCurrencyCode' => $otherWallet->defaultCurrencyCode,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('wallets', [
            'name' => $otherWallet->name,
            'slug' => $wallet->slug,
            'is_public' => $otherWallet->isPublic,
            'default_currency_code' => $otherWallet->defaultCurrencyCode,
        ]);
    }

    public function testUpdateWalletServiceThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();
        $otherWallet = WalletFactory::make();

        $this->mock(WalletService::class, ['store'], function (MockObject $mock) {
            $mock->expects($this->once())->method('store')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->put("/wallets/{$wallet->id}", [
            'name' => $otherWallet->name,
            'isPublic' => $otherWallet->isPublic,
            'defaultCurrencyCode' => $otherWallet->defaultCurrencyCode,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('wallets', [
            'name' => $otherWallet->name,
            'slug' => $wallet->slug,
            'is_public' => $otherWallet->isPublic,
            'default_currency_code' => $otherWallet->defaultCurrencyCode,
        ]);
    }

    public function testDeleteRequireAuth(): void
    {
        $wallet = $this->walletFactory->create();

        $response = $this->delete("/wallets/{$wallet->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletStillRequireAuth(): void
    {
        $walletId = Fixtures::integer();

        $response = $this->delete("/wallets/{$walletId}");

        $response->assertUnauthorized();
    }

    public function testDeleteMissingWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $walletId = Fixtures::integer();

        $response = $this->withAuth($auth)->delete("/wallets/{$walletId}");

        $response->assertNotFound();
    }

    public function testDeleteNonMemberWalletReturnNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $wallet = $this->walletFactory->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}");

        $response->assertNotFound();
    }

    public function testDeleteRemoveWallet(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('wallets', [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'slug' => $wallet->slug,
        ]);
    }

    public function testDeleteThrownException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $wallet = $this->walletFactory->forUser($user)->create();

        $this->mock(WalletService::class, ['delete'], function (MockObject $mock) {
            $mock->expects($this->once())->method('delete')->willThrowException(new \RuntimeException());
        });

        $response = $this->withAuth($auth)->delete("/wallets/{$wallet->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'slug' => $wallet->slug,
        ]);
    }
}
