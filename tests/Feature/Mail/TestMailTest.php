<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\TestMail;
use Symfony\Component\Mime\Address;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class TestMailTest extends TestCase
{
    public function testBuild(): void
    {
        $user = UserFactory::make();

        $mail = new TestMail($user);

        $mail = $mail->build();

        $to = $mail->getEmailMessage()->getTo();
        $this->assertIsArray($to);
        $this->assertCount(1, $to);
        $this->assertInstanceOf(Address::class, $to[0]);
        $this->assertEquals($user->email, $to[0]->getAddress());
        $this->assertEquals($user->fullName(), $to[0]->getName());
        $this->assertNotEmpty($mail->getEmailMessage()->getSubject());
    }
}
