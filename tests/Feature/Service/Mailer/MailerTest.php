<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Service\Mailer\Mail;
use App\Service\Mailer\Mailer;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Tests\Fixtures;
use Tests\TestCase;

class MailerTest extends TestCase
{
    public function testSend(): void
    {
        $message = new Email();

        $symfonyMailer = $this->getMockBuilder(MailerInterface::class)
                      ->onlyMethods(['send'])
                      ->getMock();

        $symfonyMailer->expects($this->once())->method('send')->with($message);

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();


        $mailer = new Mailer($symfonyMailer, $views);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['build', 'render', 'getEmailMessage'])
                     ->getMockForAbstractClass();

        $mail->method('build')->willReturnSelf();
        $mail->method('render')->with($views)->willReturnSelf();
        $mail->method('getEmailMessage')->willReturn($message);

        $mailer->send($mail);
    }

    public function testRender(): void
    {
        $content = Fixtures::string(128);

        $message = new Email();
        $message->html($content);

        $symfonyMailer = $this->getMockBuilder(MailerInterface::class)
                      ->getMock();

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();

        $mailer = new Mailer($symfonyMailer, $views);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['build', 'render', 'getEmailMessage'])
                     ->getMockForAbstractClass();

        $mail->method('build')->willReturnSelf();
        $mail->method('render')->with($views)->willReturnSelf();
        $mail->method('getEmailMessage')->willReturn($message);

        $this->assertEquals($content, $mailer->render($mail));
    }
}
