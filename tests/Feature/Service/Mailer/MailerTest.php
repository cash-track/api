<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Mailer;

use App\Jobs\SendMailJob;
use App\Service\Mailer\Mail;
use App\Service\Mailer\Mailer;
use App\Service\UserOptionsService;
use Cycle\ORM\ORMInterface;
use Spiral\Queue\Options;
use Spiral\Queue\QueueInterface;
use Spiral\Translator\Config\TranslatorConfig;
use Spiral\Translator\Translator;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Tests\Fixtures;
use Tests\TestCase;

class MailerTest extends TestCase
{
    public function testSend(): void
    {
        $payload = ['data' => 'options'];

        $symfonyMailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();
        $queue = $this->getMockBuilder(QueueInterface::class)->getMock();
        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $translator = $this->getContainer()->get(Translator::class);
        $translatorConfig = $this->getContainer()->get(TranslatorConfig::class);
        $userOptions = $this->getMockBuilder(UserOptionsService::class)->getMock();

        $queue->expects($this->once())
              ->method('push')
              ->with(SendMailJob::class, $payload, Options::onQueue(Mailer::QUEUE_NAME));


        $mailer = new Mailer($symfonyMailer, $views, $queue, $orm, $translator, $translatorConfig, $userOptions);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['toPayload'])
                     ->getMockForAbstractClass();

        $mail->method('toPayload')->willReturn($payload);

        $mailer->send($mail);
    }

    public function testSendNow(): void
    {
        $message = new Email();

        $symfonyMailer = $this->getMockBuilder(MailerInterface::class)
                              ->onlyMethods(['send'])
                              ->getMock();
        $symfonyMailer->expects($this->once())->method('send')->with($message);

        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();
        $queue = $this->getMockBuilder(QueueInterface::class)->getMock();
        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $translator = $this->getContainer()->get(Translator::class);
        $translatorConfig = $this->getContainer()->get(TranslatorConfig::class);
        $userOptions = $this->getMockBuilder(UserOptionsService::class)->getMock();

        $mailer = new Mailer($symfonyMailer, $views, $queue, $orm, $translator, $translatorConfig, $userOptions);
        $mailer->setDefaultFromAddress(Fixtures::email());
        $mailer->setDefaultFromName(Fixtures::string());

        $mail = $this->getMockBuilder(Mail::class)
                     ->onlyMethods(['hydrate', 'build', 'render', 'getEmailMessage'])
                     ->getMockForAbstractClass();

        $mail->method('hydrate')->with($orm);
        $mail->method('build')->willReturnSelf();
        $mail->method('render')->with($views)->willReturnSelf();
        $mail->method('getEmailMessage')->willReturn($message);

        $mailer->sendNow($mail);
    }

    public function testRender(): void
    {
        $content = Fixtures::string(128);

        $message = new Email();
        $message->html($content);

        $symfonyMailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $views = $this->getMockBuilder(ViewsInterface::class)->getMock();
        $queue = $this->getMockBuilder(QueueInterface::class)->getMock();
        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $translator = $this->getContainer()->get(Translator::class);
        $translatorConfig = $this->getContainer()->get(TranslatorConfig::class);
        $userOptions = $this->getMockBuilder(UserOptionsService::class)->getMock();

        $mailer = new Mailer($symfonyMailer, $views, $queue, $orm, $translator, $translatorConfig, $userOptions);
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
