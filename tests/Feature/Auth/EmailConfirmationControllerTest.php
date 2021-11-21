<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

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

        $this->userFactory = $this->app->get(UserFactory::class);
        $this->emailConfirmationFactory = $this->app->get(EmailConfirmationFactory::class);
    }

    public function testGetEmailConfirmationRequireAuth(): void
    {
        $response = $this->get('/auth/email/confirmation');
        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testGetEmailConfirmation(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $confirmation = EmailConfirmationFactory::make();
        $confirmation->email = $user->email;
        $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->get('/auth/email/confirmation');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('email', $body['data']);
        $this->assertEquals($user->email, $body['data']['email']);
    }

    public function testGetEmptyEmailConfirmation(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/auth/email/confirmation');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

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

        $response = $this->post("/auth/email/confirmation/{$confirmation->token}");

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('email_confirmations', [
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'is_email_confirmed' => true,
        ]);
    }

    public function testConfirmWithExpiredToken(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $confirmation = EmailConfirmationFactory::expired();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->post("/auth/email/confirmation/{$confirmation->token}");

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'is_email_confirmed' => false,
        ]);
    }

    public function testConfirmWithMissingToken(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $token = Fixtures::string(16);

        $response = $this->post("/auth/email/confirmation/{$token}");

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('users', [
            'email' => $user->email,
            'is_email_confirmed' => false,
        ]);
    }

    public function testConfirmMissingUser(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $confirmation = EmailConfirmationFactory::notExpired();
        $confirmation->email = Fixtures::email();
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->post("/auth/email/confirmation/{$confirmation->token}");

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));

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
        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testReSendSendsMessage(): void
    {
        $this->markTestIncomplete();

        $auth = $this->makeAuth($user = $this->userFactory->create(UserFactory::emailNotConfirmed()));

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['send', 'render'])
                     ->getMock();

        $mock->expects($this->once())
             ->method('send')
             ->with($this->callback(function ($mail) use ($user) {
                 $this->assertInstanceOf(EmailConfirmationMail::class, $mail);
                 $this->assertInstanceOf(User::class, $mail->user);
                 $this->assertEquals($user->id, $mail->user->id);

                 return true;
             }));

        $this->app->container->bind(MailerInterface::class, $mock);

        $confirmation = EmailConfirmationFactory::notThrottled();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

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
        $this->markTestIncomplete();

        $auth = $this->makeAuth($user = $this->userFactory->create(UserFactory::emailNotConfirmed()));

        $this->mockMailerNeverCalled();

        $confirmation = EmailConfirmationFactory::throttled();
        $confirmation->email = $user->email;
        $confirmation = $this->emailConfirmationFactory->create($confirmation);

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));

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
        $this->markTestIncomplete();

        $auth = $this->makeAuth($this->userFactory->create(UserFactory::emailConfirmed()));

        $this->mockMailerNeverCalled();

        $response = $this->withAuth($auth)->post("/auth/email/confirmation/resend");

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    private function mockMailerNeverCalled()
    {
        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->onlyMethods(['send', 'render'])
                     ->getMock();

        $mock->expects($this->never())->method('send');

        $this->app->container->bind(MailerInterface::class, $mock);
    }
}
