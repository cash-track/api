<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Database\User;
use App\Repository\CurrencyRepository;
use App\Request\Profile\UpdateBasicRequest;
use App\Service\UserService;
use Psr\Http\Message\ResponseInterface;
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

        $this->userFactory = $this->app->get(UserFactory::class);
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

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testIndex(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->get('/profile');

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $this->assertProfileResponse($user, $response);
    }

    private function assertProfileResponse(User $user, ResponseInterface $response, array $fields = null): void
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

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testCheckNickNamePassForCurrent(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => $user->nickName,
        ]);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testCheckNickNamePassForFree(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/check/nick-name', [
            'nickName' => Fixtures::string(),
        ]);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

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

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function testUpdateRequireAuth(): void
    {
        $response = $this->put('/profile/');

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
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
        ]);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $this->assertProfileResponse($newProfile, $response);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->lastName,
            'default_currency_code' => $user->defaultCurrencyCode,
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

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertArrayHasKey('lastName', $body['errors']);
        $this->assertArrayHasKey('nickName', $body['errors']);
        $this->assertArrayHasKey('defaultCurrencyCode', $body['errors']);
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

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
                            ->onlyMethods(['setContext', 'isValid', 'getName', 'getLastName', 'getNickName', 'getDefaultCurrencyCode'])
                            ->getMock();

        $requestMock->method('isValid')->willReturn(true);
        $requestMock->method('getName')->willReturn($newProfile->name);
        $requestMock->method('getLastName')->willReturn($newProfile->lastName);
        $requestMock->method('getNickName')->willReturn($newProfile->nickName);
        $requestMock->method('getDefaultCurrencyCode')->willReturn($missingCurrencyCode);

        $repositoryMock = $this->getMockBuilder(CurrencyRepository::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['findByPK'])
                               ->getMock();

        $repositoryMock->expects($this->once())
                       ->method('findByPK')
                       ->with($missingCurrencyCode)
                       ->willReturn(null);

        $this->app->container->bind(UpdateBasicRequest::class, $requestMock);
        $this->app->container->bind(CurrencyRepository::class, $repositoryMock);

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $newProfile->nickName,
            'defaultCurrencyCode' => $missingCurrencyCode,
        ]);

        $this->assertEquals(500, $response->getStatusCode(), $this->getResponseBody($response));

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

        $this->app->container->bind(UserService::class, $mock);

        $response = $this->withAuth($auth)->put('/profile', [
            'name' => $newProfile->name,
            'lastName' => $newProfile->lastName,
            'nickName' => $newProfile->nickName,
            'defaultCurrencyCode' => $newProfile->defaultCurrencyCode,
        ]);

        $this->assertEquals(500, $response->getStatusCode(), $this->getResponseBody($response));

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
}
