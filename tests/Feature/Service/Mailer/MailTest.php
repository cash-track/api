<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Service\Mailer\Mail;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\Fixtures;
use Tests\TestCase;

class MailTest extends TestCase
{
    public function testSend(): void
    {
        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $this->assertInstanceOf(Email::class, $mail->getEmailMessage());
    }

    public function testSubject(): void
    {
        $subject = Fixtures::string(16);

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->subject($subject);

        $this->assertEquals($subject, $mail->getEmailMessage()->getSubject());
    }

    public function testFrom(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->from($address, $fullName);

        $data = $mail->getEmailMessage()->getFrom();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Address::class, $data[0]);
        $this->assertEquals($address, $data[0]->getAddress());
        $this->assertEquals($fullName, $data[0]->getName());
    }

    public function testTo(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->to($address, $fullName);

        $data = $mail->getEmailMessage()->getTo();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Address::class, $data[0]);
        $this->assertEquals($address, $data[0]->getAddress());
        $this->assertEquals($fullName, $data[0]->getName());
    }

    public function testReplyTo(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->replyTo($address, $fullName);

        $data = $mail->getEmailMessage()->getReplyTo();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Address::class, $data[0]);
        $this->assertEquals($address, $data[0]->getAddress());
        $this->assertEquals($fullName, $data[0]->getName());
    }

    public function testCc(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->cc($address, $fullName);

        $data = $mail->getEmailMessage()->getCc();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Address::class, $data[0]);
        $this->assertEquals($address, $data[0]->getAddress());
        $this->assertEquals($fullName, $data[0]->getName());
    }

    public function testBcc(): void
    {
        $address = Fixtures::email();
        $fullName = Fixtures::string();

        $mail = $this->getMockBuilder(Mail::class)->getMockForAbstractClass();

        $mail->bcc($address, $fullName);

        $data = $mail->getEmailMessage()->getBcc();
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertInstanceOf(Address::class, $data[0]);
        $this->assertEquals($address, $data[0]->getAddress());
        $this->assertEquals($fullName, $data[0]->getName());
    }

    public function testRender(): void
    {
        $viewName = Fixtures::string();
        $rendered = Fixtures::string(64);
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

        $this->assertEquals($rendered, $mail->getEmailMessage()->getBody()->bodyToString());
    }

    public function testEmptyDeserializerAttr()
    {
        $mail = TestMail::fromPayload([
            'class' => TestMail::class,
        ]);
        $this->assertInstanceOf(TestMail::class, $mail);
    }
}
