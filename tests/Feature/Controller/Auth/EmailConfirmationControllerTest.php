<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Database\EntityHeader;
use App\Database\User;
use App\Mail\EmailConfirmationMail;
use App\Service\Mailer\MailerInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\EmailConfirmationFactory;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class EmailConfirmationControllerTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var \Tests\Factories\EmailConfirmationFactory
     */
    protected EmailConfirmationFactory $emailConfirmationFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->emailConfirmationFactory = $this->getContainer()->get(EmailConfirmationFactory::class);
    }

    public function testGetEmailConfirmationRequireAuth(): void
    {
        $response = $this->get('/auth/email/confirmation');

        $response->assertUnauthorized();
    }

    public function testGetEmailConfirmation(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $confirmation = EmailConfirmationFactory::make();
        $confirmation->email = $user->email;
        $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->get('/auth/email/confirmation');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('email', $body['data']);
        $this->assertEquals($user->email, $body['data']['email']);
    }

    public function testGetEmptyEmailConfirmation(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/auth/email/confirmation');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertNull($body['data']);
    }

    public function testConfirm(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $confirmation = EmailConfirmationFactory::notExpired();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->post("/auth/email/confirmation/confirm/{$confirmation->token}");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('email_confirmations', [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('users', [
            'is_email_confirmed' => true,
        ], [
            'email' => $user->email,
        ]);
    }

    public function testConfirmWithExpiredToken(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $confirmation = EmailConfirmationFactory::expired();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->post("/auth/email/confirmation/confirm/{$confirmation->token}");

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('users', [
            'is_email_confirmed' => false,
        ], [
            'email' => $user->email,
        ]);
    }

    public function testConfirmWithMissingToken(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $token = Fixtures::string(16);

        $response = $this->post("/auth/email/confirmation/confirm/{$token}");

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('users', [
            'is_email_confirmed' => false,
        ], [
            'email' => $user->email,
        ]);
    }

    public function testConfirmMissingUser(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $confirmation = EmailConfirmationFactory::notExpired();
        $confirmation->email = Fixtures::email();
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->post("/auth/email/confirmation/confirm/{$confirmation->token}");

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseMissing('users', [
            'email' => $user->email,
            'is_email_confirmed' => true,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => $confirmation->email,
            'is_email_confirmed' => true,
        ]);
    }

    public function testReSendRequireAuth(): void
    {
        $response = $this->post('/auth/email/confirmation/resend');

        $response->assertUnauthorized();
    }

    public function testReSendSendsMessage(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create(UserFactory::emailNotConfirmed()));

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('send')
             ->with($this->callback(function ($mail) use ($user) {
                 $this->assertInstanceOf(EmailConfirmationMail::class, $mail);
                 $this->assertInstanceOf(EntityHeader::class, $mail->userHeader);
                 $this->assertEquals(User::class, $mail->userHeader->role);
                 $this->assertEquals(['id' => $user->id], $mail->userHeader->params);

                 return true;
             }));

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);

        $confirmation = EmailConfirmationFactory::notThrottled();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('email_confirmations', [
            'email' => $confirmation->email,
            'token' => $confirmation->token,
        ]);

        $this->assertDatabaseHas('email_confirmations', [
            'email' => $confirmation->email,
        ]);
    }

    public function testReSendThrottled(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create(UserFactory::emailNotConfirmed()));

        $this->mockMailerNeverCalled();

        $confirmation = EmailConfirmationFactory::throttled();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('email_confirmations', [
            'email' => $confirmation->email,
            'token' => $confirmation->token,
        ]);
    }

    public function testReSendRejectAlreadyConfirmed(): void
    {
        $auth = $this->makeAuth($this->userFactory->create(UserFactory::emailConfirmed()));

        $this->mockMailerNeverCalled();

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    private function mockMailerNeverCalled()
    {
        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->never())->method('send');

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);
    }
}
