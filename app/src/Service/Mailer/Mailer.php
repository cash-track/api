<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use Spiral\Views\ViewsInterface;

class Mailer implements MailerInterface
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Spiral\Views\ViewInterface
     */
    private $views;

    /**
     * Mailer constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param \Spiral\Views\ViewsInterface $views
     */
    public function __construct(\Swift_Mailer $mailer, ViewsInterface $views)
    {
        $this->mailer = $mailer;
        $this->views = $views;
    }

    /**
     * Compile, render and send given mail using previously configured transport.
     *
     * @param \App\Service\Mailer\Mail $message
     * @return void
     */
    public function send(Mail $message): void
    {
        $this->mailer->send($message->build()->render($this->views)->getSwiftMessage());
    }

    /**
     * Compile mail template with injected variables.
     *
     * @param \App\Service\Mailer\Mail $message
     * @return string
     */
    public function render(Mail $message): string
    {
        return $message->build()->render($this->views)->getSwiftMessage()->getBody();
    }
}
