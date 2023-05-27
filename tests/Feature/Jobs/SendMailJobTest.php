<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\SendMailJob;
use App\Mail\TestMail;
use App\Service\Mailer\Mail;
use App\Service\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class SendMailJobTest extends TestCase
{
    public function testInvoke(): void
    {
        $user = UserFactory::make();
        $mail = new TestMail($user->getEntityHeader());

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->once())->method('sendNow')->with($this->callback(function (Mail $mail) {
            $this->assertInstanceOf(TestMail::class, $mail);
            return true;
        }));

        /** @var SendMailJob $job */
        $job = $this->getContainer()->get(SendMailJob::class);
        $job->invoke(Fixtures::string(), $mail->toPayload(), [], $logger, $mailer);
    }

    public function testInvokeUnexpectedMail(): void
    {
        $user = UserFactory::make();

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $mailer = $this->getMockBuilder(MailerInterface::class)->getMock();
        $mailer->expects($this->never())->method('sendNow');

        /** @var SendMailJob $job */
        $job = $this->getContainer()->get(SendMailJob::class);
        $job->invoke(Fixtures::string(), $user->getEntityHeader()->toPayload(), [], $logger, $mailer);
    }
}
