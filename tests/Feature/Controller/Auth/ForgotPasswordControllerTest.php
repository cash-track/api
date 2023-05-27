<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Database\EntityHeader;
use App\Database\User;
use App\Mail\ForgotPasswordMail;
use App\Repository\ForgotPasswordRequestRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\MailerInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\ForgotPasswordRequestFactory;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\AuthAsserts;
use Tests\Fixtures;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase implements DatabaseTransaction
{
    use AuthAsserts;

    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var \Tests\Factories\ForgotPasswordRequestFactory
     */
    protected ForgotPasswordRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->requestFactory = $this->getContainer()->get(ForgotPasswordRequestFactory::class);
    }

    public function testCreate(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('send')
             ->with($this->callback(function ($mail) use ($user) {
                $this->assertInstanceOf(ForgotPasswordMail::class, $mail);
                $this->assertInstanceOf(EntityHeader::class, $mail->userHeader);
                $this->assertEquals(User::class, $mail->userHeader->role);
                $this->assertEquals(['id' => $user->id], $mail->userHeader->params);

                return true;
             }));

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseHas('forgot_password_requests', [
            'email' => $user->email,
        ]);
    }

    public function testCreateValidationFails(): void
    {
        $response = $this->post('/auth/password/forgot', [
            'email' => Fixtures::email(),
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('email', $body['errors']);
    }

    public function testCreateThrottled(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $forgotPasswordRequest = ForgotPasswordRequestFactory::throttled();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);


        $this->assertDatabaseCount(1, 'forgot_password_requests', [
            'email' => $user->email,
        ]);
    }

    public function testCreateRemovesExpiredRequest(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('send');

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $forgotPasswordRequest = ForgotPasswordRequestFactory::expired();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseCount(1, 'forgot_password_requests', [
            'email' => $user->email,
        ]);

        $this->assertDatabaseMissing('forgot_password_requests', [
            'email' => $user->email,
            'code' => $forgotPasswordRequest->code,
        ]);
    }

    public function testCreateUnableToSendMessageFails(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('send')
             ->willThrowException(new \RuntimeException('Transport exception'));

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testCreateCannotResolveUserFails(): void
    {
        $user = $this->userFactory->create();

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->never())
             ->method('send');

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $mock = $this->getMockBuilder(UserRepository::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['findByEmail', 'findOne'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('findByEmail')
             ->willReturn(null);

        $mock->expects($this->once())
             ->method('findOne')
             ->willReturn(ForgotPasswordRequestFactory::make());

        $this->getContainer()->bind(UserRepository::class, fn () => $mock);

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('forgot_password_requests', [
            'email' => $user->email,
        ]);
    }

    public function testResetUpdatesPassword(): void
    {
        $user = $this->userFactory->create();

        $forgotPasswordRequest = ForgotPasswordRequestFactory::throttled();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $password = Fixtures::string();

        $response = $this->post('/auth/password/reset', [
            'code' => $forgotPasswordRequest->code,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('forgot_password_requests', [
            'email' => $user->email,
        ]);

        $this->assertUserCanLogin($user, $password);
    }

    public function testResetValidationFailsWithEmptyRequest(): void
    {
        $response = $this->post('/auth/password/reset', [
            'code' => '',
            'password' => '',
            'passwordConfirmation' => '',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('code', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
    }

    public function testResetValidationFailsWithInvalidRequest(): void
    {
        $password = Fixtures::string(5);
        $response = $this->post('/auth/password/reset', [
            'code' => Fixtures::string(),
            'password' => $password,
            'passwordConfirmation' => $password . '.',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('code', $body['errors']);
        $this->assertArrayHasKey('password', $body['errors']);
        $this->assertArrayHasKey('passwordConfirmation', $body['errors']);
    }

    public function testResetFailsWithExpiredRequest(): void
    {
        $user = $this->userFactory->create();

        $forgotPasswordRequest = ForgotPasswordRequestFactory::expired();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $password = Fixtures::string();

        $response = $this->post('/auth/password/reset', [
            'code' => $forgotPasswordRequest->code,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertUserCannotLogin($user, $password);
    }

    public function testResetFailsByMissingRequest(): void
    {
        $user = $this->userFactory->create();

        $forgotPasswordRequest = ForgotPasswordRequestFactory::notExpired();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $mock = $this->getMockBuilder(ForgotPasswordRequestRepository::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['findByCode', 'findOne'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('findByCode')
             ->with($forgotPasswordRequest->code)
             ->willReturn(null);

        $mock->expects($this->once())
             ->method('findOne')
             ->willReturn($forgotPasswordRequest);

        $this->getContainer()->bind(ForgotPasswordRequestRepository::class, fn () => $mock);

        $password = Fixtures::string();

        $response = $this->post('/auth/password/reset', [
            'code' => $forgotPasswordRequest->code,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertUserCannotLogin($user, $password);
    }

    public function testResetFailsByMissingUser(): void
    {
        $user = $this->userFactory->create();

        $forgotPasswordRequest = ForgotPasswordRequestFactory::notExpired();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $mock = $this->getMockBuilder(UserRepository::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['findByEmail'])
                     ->getMock();

        $mock->expects($this->atLeastOnce())
             ->method('findByEmail')
             ->with($user->email)
             ->willReturn($this->onConsecutiveCalls(null, $user));

        $this->getContainer()->bind(UserRepository::class, fn () => $mock);

        $password = Fixtures::string();

        $response = $this->post('/auth/password/reset', [
            'code' => $forgotPasswordRequest->code,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertUserCannotLogin($user, $password);
    }
}
