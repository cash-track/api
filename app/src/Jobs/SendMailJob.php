<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Service\Mailer\Mail;
use App\Service\Mailer\MailerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Queue\JobHandler;

final class SendMailJob extends JobHandler
{
    public function invoke(
        string $id,
        array $payload,
        array $headers,
        LoggerInterface $logger,
        MailerInterface $mailer,
    ): void {
        $mail = Mail::fromPayload($payload);

        if (! $mail instanceof Mail) {
            $logger->error('Unexpected mail in payload', [
                'id' => $id,
                'exception' => new \UnexpectedValueException(sprintf('%s is not a Mail', $mail::class)),
            ]);
            return;
        }

        $logger->debug('Sending mail job', [
            'id' => $id,
            'mail' => $mail::class,
        ]);

        $mailer->sendNow($mail);
    }
}
