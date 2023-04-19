<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Profile;

use App\Database\User;
use App\Repository\CurrencyRepository;
use App\Request\Profile\UpdateBasicRequest;
use App\Service\UserService;
use Spiral\Testing\Http\TestResponse;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ProfileControllerTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    private function userFields(): array
    {
        return [
            'id',
            'name',
            'lastName',
            'nickName',
            'email',
            'isEmailConfirmed',
            'photoUrl',
            'createdAt',
            'updatedAt',
            'defaultCurrencyCode',
            'defaultCurrency',
        ];
    }

    public function testIndexRequireAuth(): void
    {
        $response = $this->get('/profile');

        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertOk();

        $this->assertProfileResponse($user, $response);
    }

    private function assertProfileResponse(User $user, TestResponse $response, array $fields = null): void
    {
        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('type', $body['data']);
        $this->assertArrayHasKey('id', $body['data']);

        $this->assertEquals($body['data']['type'], 'user');

        if ($user->id !== null) {
            $this->assertEquals($body['data']['id'], $user->id);
        }

        foreach ($fields ?? $this->userFields() as $field) {
            $this->assertArrayHasKey($field, $body['data'], "Field {$field}");
        }
    }

    public function testCheckNickNameRequireAuth(): void
    {
        $response = $this->post('/profile/check/nick-name');

        $response->assertUnauthorized();
    }

    public function testCheckNickNamePassForCurrent(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => $user->nickName,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testCheckNickNamePassForFree(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => Fixtures::string(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testCheckNickNameFailsForClaimed(): void
    {
        $existingUser = $this->userFactory->create();

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => $existingUser->nickName,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function provideInvalidNickNames(): array
    {
        return UserFactory::invalidNickNames();
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param string $nickName
     * @return void
     */
    public function testCheckNickNameFailsForInvalid($nickName): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => $nickName,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function testUpdateRequireAuth(): void
    {
        $response = $this->put('/profile/');

        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $newProfile = UserFactory::make();

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $newProfile->nickName,
            'defaultCurrencyCode' => $newProfile->defaultCurrencyCode,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertOk();

        $this->assertProfileResponse($newProfile, $response);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'default_currency_code' => $user->defaultCurrencyCode,
        ], [
            'name' => $user->name,
            'last_name' => $user->lastName,
        ]);
    }

    public function testUpdateFailsDueEmptyForm(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => null,
            'lastName' => null,
            'nickName' => null,
            'defaultCurrencyCode' => null,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertArrayHasKey('nickName', $body['errors']);
        $this->assertArrayHasKey('defaultCurrencyCode', $body['errors']);
    }

    public function testUpdateFailsDueInvalidForm(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => 123,
            'lastName' => 123,
            'nickName' => 123,
            'defaultCurrencyCode' => 123,
            'locale' => 123,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertArrayHasKey('lastName', $body['errors']);
        $this->assertArrayHasKey('nickName', $body['errors']);
        $this->assertArrayHasKey('defaultCurrencyCode', $body['errors']);
        $this->assertArrayHasKey('locale', $body['errors']);
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param $nickName
     * @return void
     */
    public function testUpdateFailsDueInvalidNickName($nickName): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $newProfile = UserFactory::make();

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $nickName,
            'defaultCurrencyCode' => $newProfile->defaultCurrencyCode,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function testUpdateFailsDueClaimedNickName(): void
    {
        $existingUser = $this->userFactory->create();
        $auth = $this->makeAuth($this->userFactory->create());
        $newProfile = UserFactory::make();

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $existingUser->nickName,
            'defaultCurrencyCode' => $newProfile->defaultCurrencyCode,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function testUpdateFailsWithMissingCurrency(): void
    {
        $missingCurrencyCode = Fixtures::string(3);
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $newProfile = UserFactory::make();

        $requestMock = $this->getMockBuilder(UpdateBasicRequest::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $requestMock->name = $newProfile->name;
        $requestMock->lastName = $newProfile->lastName;
        $requestMock->nickName = $newProfile->nickName;
        $requestMock->defaultCurrencyCode = $missingCurrencyCode;

        $repositoryMock = $this->getMockBuilder(CurrencyRepository::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['findByPK'])
                               ->getMock();

        $repositoryMock->expects($this->once())
                       ->method('findByPK')
                       ->with($missingCurrencyCode)
                       ->willReturn(null);

        $this->getContainer()->bind(UpdateBasicRequest::class, fn () => $requestMock);
        $this->getContainer()->bind(CurrencyRepository::class, fn () => $repositoryMock);

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $newProfile->nickName,
            'defaultCurrencyCode' => $missingCurrencyCode,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'default_currency_code' => $missingCurrencyCode,
        ]);
    }

    public function testUpdateFailsWithStorageException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $newProfile = UserFactory::make();

        $mock = $this->getMockBuilder(UserService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['store'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('store')
             ->willThrowException(new \RuntimeException('Storage exception.'));

        $this->getContainer()->bind(UserService::class, $mock);

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $newProfile->nickName,
            'defaultCurrencyCode' => $newProfile->defaultCurrencyCode,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'name' => $newProfile->name,
            'last_name' => $newProfile->lastName,
            'nick_name' => $newProfile->nickName,
            'default_currency_code' => $newProfile->defaultCurrencyCode,
        ]);
    }

    public function testUpdateLocaleRequireAuth(): void
    {
        $response = $this->put('/profile/locale');

        $response->assertUnauthorized();
    }

    public function testUpdateLocale(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $newLocale = UserFactory::locale();

        $response = $this->withAuth($auth)->put('/profile/locale', [
            'locale' => $newLocale,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('type', $body['data']);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertEquals($body['data']['type'], 'user');
        $this->assertEquals($body['data']['id'], $user->id);
        $this->assertArrayHasKey('locale', $body['data']);
        $this->assertEquals($body['data']['locale'], $newLocale);
    }

    public function testUpdateLocaleFailsDueInvalidForm(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->put('/profile/locale', [
            'locale' => 123,
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('locale', $body['errors']);
    }

    public function testUpdateLocaleFailsWithStorageException(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $mock = $this->getMockBuilder(UserService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['store'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('store')
             ->willThrowException(new \RuntimeException('Storage exception.'));

        $this->getContainer()->bind(UserService::class, $mock);

        $response = $this->withAuth($auth)->put('/profile/locale', [
            'locale' => UserFactory::locale(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }
}
