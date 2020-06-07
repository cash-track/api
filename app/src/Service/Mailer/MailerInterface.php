<?php

declare(strict_types=1);

namespace App\Service\Mailer;

interface MailerInterface
{
    /**
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function send(Mail $mail): void;

    /**
     * @param \App\Service\Mailer\Mail $message
     * @return string
     */
    public function render(Mail $message): string;
}
