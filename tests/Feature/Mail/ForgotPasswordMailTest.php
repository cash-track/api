<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\ForgotPasswordMail;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class ForgotPasswordMailTest extends TestCase
{
    public function testBuild(): void
    {
        $user = UserFactory::make();
        $link = Fixtures::url();

        $mail = new ForgotPasswordMail($user, $link);

        $mail = $mail->build();

        $this->assertArrayHasKey($user->email, $mail->getSwiftMessage()->getTo());
        $this->assertContains($user->fullName(), $mail->getSwiftMessage()->getTo());
        $this->assertNotEmpty($mail->getSwiftMessage()->getSubject());
    }
}
