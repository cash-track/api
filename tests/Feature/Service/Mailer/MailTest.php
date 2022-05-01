<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Service\Mailer\Mail;
use Spiral\Views\ViewsInterface;
use Tests\Fixtures;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function testSend(): void
    {
        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $this->assertInstanceOf(\Swift_Message::class, $mail->getSwiftMessage());
    }

    public function testSubject(): void
    {
        $subject = Fixtures::string(16);

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->subject($subject);

        $this->assertEquals($subject, $mail->getSwiftMessage()->getSubject());
    }

    public function testFrom(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->from($address, $fullName);

        $this->assertIsArray($mail->getSwiftMessage()->getFrom());
        $this->assertArrayHasKey($address, $mail->getSwiftMessage()->getFrom());
        $this->assertEquals($fullName, $mail->getSwiftMessage()->getFrom()[$address] ?? null);
    }

    public function testTo(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->to($address, $fullName);

        $this->assertIsArray($mail->getSwiftMessage()->getTo());
        $this->assertArrayHasKey($address, $mail->getSwiftMessage()->getTo());
        $this->assertEquals($fullName, $mail->getSwiftMessage()->getTo()[$address] ?? null);
    }

    public function testReplyTo(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->replyTo($address, $fullName);

        $this->assertIsArray($mail->getSwiftMessage()->getReplyTo());
        $this->assertArrayHasKey($address, $mail->getSwiftMessage()->getReplyTo());
        $this->assertEquals($fullName, $mail->getSwiftMessage()->getReplyTo()[$address] ?? null);
    }

    public function testCc(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->cc($address, $fullName);

        $this->assertIsArray($mail->getSwiftMessage()->getCc());
        $this->assertArrayHasKey($address, $mail->getSwiftMessage()->getCc());
        $this->assertEquals($fullName, $mail->getSwiftMessage()->getCc()[$address] ?? null);
    }

    public function testBcc(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->bcc($address, $fullName);

        $this->assertIsArray($mail->getSwiftMessage()->getBcc());
        $this->assertArrayHasKey($address, $mail->getSwiftMessage()->getBcc());
        $this->assertEquals($fullName, $mail->getSwiftMessage()->getBcc()[$address] ?? null);
    }

    public function testRender(): void
    {
        $viewName = Fixtures::string();
        $rendered = Fixtures::string(1024);
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();

        $views->expects($this->once())
              ->method('render')
              ->with($viewName, ['email' => $address, 'fullName' => $fullName,])
              ->willReturn($rendered);

        $mail = new TestMail();
        $mail->email = $address;
        $mail->fullName = $fullName;
        $mail->setToken(Fixtures::string());

        $mail->view($viewName);

        $mail->render($views);

        $this->assertEquals($rendered, $mail->getSwiftMessage()->getBody());
    }
}
