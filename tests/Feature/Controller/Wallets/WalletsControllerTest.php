<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Wallets;

use App\Repository\CurrencyRepository;
use App\Service\WalletService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\Fixtures;
use Tests\TestCase;

class WalletsControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
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
