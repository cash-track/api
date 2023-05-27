<?php

declare(strict_types=1);

namespace App\Service\Mailer;

interface MailerInterface
{
    /**
     * Send mail using queue pipeline
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function send(Mail $mail): void;

    /**
     * Send mail right now in current context
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function sendNow(Mail $mail): void;

    /**
     * Render mail HTML for preview
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return string
     */
    public function render(Mail $mail): string;
}
