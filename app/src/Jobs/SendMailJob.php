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
                'payload' => print_r($mail, true),
            ]);
            return;
        }

        $logger->info('Sending mail job', [
            'id' => $id,
            'payload' => get_class($mail),
            'headers' => $headers,
        ]);

        $mailer->sendNow($mail);
    }
}
