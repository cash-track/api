<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Database\EntityHeader;
use App\Database\User;
use App\Mail\WelcomeMail;
use App\Service\Mailer\MailerInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\EmailConfirmationFactory;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ConfirmEmailControllerTest extends TestCase implements DatabaseTransaction
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

    public function testConfirm(): void
    {
        $user = $this->userFactory->create(UserFactory::emailNotConfirmed());

        $mock = $this->getMockBuilder(MailerInterface::class)
                     ->disableOriginalConstructor()
                     ->getMock();

        $mock->expects($this->once())
             ->method('send')
             ->with($this->callback(function ($mail) use ($user) {
                 $this->assertInstanceOf(WelcomeMail::class, $mail);
                 $this->assertInstanceOf(EntityHeader::class, $mail->userHeader);
                 $this->assertEquals(User::class, $mail->userHeader->role);
                 $this->assertEquals(['id' => $user->id], $mail->userHeader->params);

                 return true;
             }));

        $this->getContainer()->bind(MailerInterface::class, fn () => $mock);


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
}
