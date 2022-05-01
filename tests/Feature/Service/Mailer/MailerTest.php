<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Service\Mailer\Mail;
use App\Service\Mailer\Mailer;
use Spiral\Views\ViewsInterface;
use Tests\Fixtures;
use Tests\TestCase;

class MailerTest extends TestCase
{
    public function testSend(): void
    {
        $swiftMessage = new \Swift_Message();

        $swift = $this->getMockBuilder(\Swift_Mailer::class)
                      ->onlyMethods(['send'])
                      ->disableOriginalConstructor()
                      ->getMock();

        $swift->expects($this->once())->method('send')->with($swiftMessage);

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();


        $mailer = new Mailer($swift, $views);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['build', 'render', 'getSwiftMessage'])
                     ->getMockForAbstractClass();

        $mail->method('build')->willReturnSelf();
        $mail->method('render')->with($views)->willReturnSelf();
        $mail->method('getSwiftMessage')->willReturn($swiftMessage);

        $mailer->send($mail);
    }

    public function testRender(): void
    {
        $content = Fixtures::string(128);

        $swiftMessage = new \Swift_Message();
        $swiftMessage->setBody($content, 'text/html', 'utf-8');

        $swift = $this->getMockBuilder(\Swift_Mailer::class)
                      ->disableOriginalConstructor()
                      ->getMock();

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();

        $mailer = new Mailer($swift, $views);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['build', 'render', 'getSwiftMessage'])
                     ->getMockForAbstractClass();

        $mail->method('build')->willReturnSelf();
        $mail->method('render')->with($views)->willReturnSelf();
        $mail->method('getSwiftMessage')->willReturn($swiftMessage);

        $this->assertEquals($content, $mailer->render($mail));
    }
}
