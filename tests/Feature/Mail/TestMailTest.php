<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\TestMail;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class TestMailTest extends TestCase
{
    public function testBuild(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user);

        $mail = $mail->build();

        $this->assertArrayHasKey($user->email, $mail->getSwiftMessage()->getTo());
        $this->assertContains($user->fullName(), $mail->getSwiftMessage()->getTo());
        $this->assertNotEmpty($mail->getSwiftMessage()->getSubject());
    }
}
