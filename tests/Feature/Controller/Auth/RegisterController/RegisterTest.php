<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth\RegisterController;

use App\Service\Auth\EmailConfirmationService;
use App\Service\UserService;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class RegisterTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);

        $email = $this->getMockBuilder(EmailConfirmationService::class)
                      ->disableOriginalConstructor()
                      ->onlyMethods(['create'])
                      ->getMock();

        $this->getContainer()->bind(EmailConfirmationService::class, fn () => $email);
    }

    public function testUserCreated(): void
    {
        $mock = $this->getMockBuilder(EmailConfirmationService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['create'])
                     ->getMock();

        $mock->expects($this->once())->method('create');

        $this->getContainer()->bind(EmailConfirmationService::class, fn () => $mock);

        $user = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
            'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);

        $this->assertDatabaseHas('users', [], ['email' => $user->email]);
    }

    public function testUserStoreFailed(): void
    {
        $mock = $this->getMockBuilder(UserService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['store'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('store')
             ->willThrowException(new \RuntimeException('Database exception'));

        $this->getContainer()->bind(UserService::class, fn () => $mock);

        $user = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
            'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('users', ['email' => $user->email]);
        $this->assertDatabaseMissing('email_confirmations', ['email' => $user->email]);
    }

    public function testEmailConfirmationServiceFailStillStoreUser(): void
    {
        $mock = $this->getMockBuilder(EmailConfirmationService::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['create'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('create')
             ->willThrowException(new \RuntimeException('Transport exception'));

        $this->getContainer()->bind(EmailConfirmationService::class, fn () => $mock);

        $user = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
            'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);

        $this->assertDatabaseHas('users', [], ['email' => $user->email]);
    }

    public function testValidationFailsByEmptyForm(): void
    {
        $response = $this->post('/auth/register', [
            'name' => '',
            'nickName' => '',
            'email' => '',
            'password' => '',
            'passwordConfirmation' => '',
            'locale' => '',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertArrayHasKey('nickName', $body['errors']);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
        $this->assertArrayHasKey('locale', $body['errors']);
    }

    public function testValidationFailsByShortPassword(): void
    {
        $user = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => Fixtures::string(5),
            'passwordConfirmation' => Fixtures::string(5),
            'locale' => UserFactory::locale(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('password', $body['errors']);
        $this->assertArrayHasKey('passwordConfirmation', $body['errors']);
    }

    public function testValidationFailsByNickNameExists(): void
    {
        $existingUser = $this->userFactory->create();

        $newUser = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $existingUser->nickName,
            'email' => $newUser->email,
            'password' => Fixtures::string(),
            'passwordConfirmation' => Fixtures::string(),
            'locale' => UserFactory::locale(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param string $nickName
     */
    public function testValidationFailsByNickNameInvalid(string $nickName): void
    {
        $response = $this->post('/auth/register', [
            'name' => Fixtures::string(),
            'nickName' => $nickName,
            'email' => Fixtures::email(),
            'password' => Fixtures::string(),
            'passwordConfirmation' => Fixtures::string(),
            'locale' => UserFactory::locale(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('nickName', $body['errors']);
    }

    public function provideInvalidNickNames(): array
    {
        return array_merge([
            ['',],
            ['as',],
        ], array_map(
            fn ($item) => [Fixtures::string() . $item],
            str_split('!@#$%^&*()-=+"\<>,.\''),
        ));
    }

    public function testValidationFailsByEmailExists(): void
    {
        $existingUser = $this->userFactory->create();

        $newUser = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $newUser->nickName,
            'email' => $existingUser->email,
            'password' => Fixtures::string(),
            'passwordConfirmation' => Fixtures::string(),
            'locale' => UserFactory::locale(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
    }

    /**
     * @dataProvider provideInvalidEmails
     * @param string $email
     */
    public function testValidationFailsByEmailInvalid(string $email): void
    {
        $newUser = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $newUser->nickName,
            'email' => $email,
            'password' => Fixtures::string(),
            'passwordConfirmation' => Fixtures::string(),
            'locale' => UserFactory::locale(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
    }

    public function provideInvalidEmails(): array
    {
        return [
            ['me@'],
            ['@example.com'],
            ['me.@example.com'],
            ['.me@example.com'],
            ['me@example..com'],
            ['me.example@com'],
            ['me\@example.com'],
        ];
    }
}
