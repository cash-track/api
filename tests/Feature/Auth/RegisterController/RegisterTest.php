<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\RegisterController;

use App\Service\Mailer\MailerInterface;
use App\Service\UserService;
use Tests\DatabaseTransaction;
use Tests\Fixtures\Fixture;
use Tests\Fixtures\Users;
use Tests\TestCase;

class RegisterTest extends TestCase implements DatabaseTransaction
{
    public function setUp(): void
    {
        parent::setUp();

        $mailer = $this->getMockBuilder(MailerInterface::class)
                       ->onlyMethods(['send', 'render']);

        $this->app->container->bind(MailerInterface::class, $mailer);
    }

    public function testUserCreated(): void
    {
        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['send', 'render'])
                     ->getMock();

        $mock->expects($this->once())->method('send');

        $this->app->container->bind(MailerInterface::class, $mock);

        $user = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => Users::DEFAULT_PASSWORD,
            'passwordConfirmation' => Users::DEFAULT_PASSWORD,
        ]);

        $body = $this->getJsonResponseBody($response);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertDatabaseHas('email_confirmations', ['email' => $user->email]);
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

        $this->app->container->bind(UserService::class, $mock);

        $user = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => Users::DEFAULT_PASSWORD,
            'passwordConfirmation' => Users::DEFAULT_PASSWORD,
        ]);

        $this->assertEquals(500, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('users', ['email' => $user->email]);
        $this->assertDatabaseMissing('email_confirmations', ['email' => $user->email]);
    }

    public function testSendConfirmationMailFailed(): void
    {
        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['send', 'render'])
                     ->getMock();

        $mock->expects($this->once())->method('send');

        $mock->expects($this->once())
             ->method('send')
             ->willThrowException(new \RuntimeException('Transport exception'));

        $this->app->container->bind(MailerInterface::class, $mock);

        $user = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => Users::DEFAULT_PASSWORD,
            'passwordConfirmation' => Users::DEFAULT_PASSWORD,
        ]);

        $body = $this->getJsonResponseBody($response);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);

        $this->assertDatabaseHas('users', ['email' => $user->email]);
        $this->assertDatabaseHas('email_confirmations', ['email' => $user->email]);
    }

    public function testValidationFailsByEmptyForm(): void
    {
        $response = $this->post('/auth/register', [
            'name' => '',
            'nickName' => '',
            'email' => '',
            'password' => '',
            'passwordConfirmation' => ''
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('name', $body['errors']);
        $this->assertArrayHasKey('nickName', $body['errors']);
        $this->assertArrayHasKey('email', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function testValidationFailsByShortPassword(): void
    {
        $user = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => Fixture::string(5),
            'passwordConfirmation' => Fixture::string(5),
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('password', $body['errors']);
        $this->assertArrayHasKey('passwordConfirmation', $body['errors']);
    }

    public function testValidationFailsByNickNameExists(): void
    {
        $existingUser = Users::default();
        $this->app->get(UserService::class)->store($existingUser);

        $newUser = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $existingUser->nickName,
            'email' => $newUser->email,
            'password' => Fixture::string(),
            'passwordConfirmation' => Fixture::string(),
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
            'name' => Fixture::string(),
            'nickName' => $nickName,
            'email' => Fixture::email(),
            'password' => Fixture::string(),
            'passwordConfirmation' => Fixture::string(),
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
            fn ($item) => [Fixture::string() . $item],
            str_split('!@#$%^&*()-=+"\<>,.\''),
        ));
    }

    public function testValidationFailsByEmailExists(): void
    {
        $existingUser = Users::default();
        $this->app->get(UserService::class)->store($existingUser);

        $newUser = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $newUser->nickName,
            'email' => $existingUser->email,
            'password' => Fixture::string(),
            'passwordConfirmation' => Fixture::string(),
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
        $newUser = Users::default();

        $response = $this->post('/auth/register', [
            'name' => $newUser->name,
            'nickName' => $newUser->nickName,
            'email' => $email,
            'password' => Fixture::string(),
            'passwordConfirmation' => Fixture::string(),
        ]);

        $this->assertEquals(422, $response->getStatusCode(), $this->getResponseBody($response));

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
