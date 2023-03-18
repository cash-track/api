<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer implements MailerInterface
{
    /**
     * @var \Symfony\Component\Mailer\MailerInterface
     */
    private $mailer;

    /**
     * @var \Spiral\Views\ViewsInterface
     */
    private $views;

    /**
     * @var string
     */
    private $defaultFromName = '';

    /**
     * @var string
     */
    private $defaultFromAddress = '';

    /**
     * Mailer constructor.
     *
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Spiral\Views\ViewsInterface $views
     */
    public function __construct(SymfonyMailerInterface $mailer, ViewsInterface $views)
    {
        $this->mailer = $mailer;
        $this->views = $views;
    }

    /**
     * @param string $defaultFromName
     * @return \App\Service\Mailer\MailerInterface
     */
    public function setDefaultFromName(string $defaultFromName): MailerInterface
    {
        $this->defaultFromName = $defaultFromName;

        return $this;
    }

    /**
     * @param string $defaultFromAddress
     * \App\Service\Mailer\MailerInterface
     */
    public function setDefaultFromAddress(string $defaultFromAddress): MailerInterface
    {
        $this->defaultFromAddress = $defaultFromAddress;

        return $this;
    }

    /**
     * Compile, render and send given mail using previously configured transport.
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function send(Mail $mail): void
    {
        $this->mailer->send($this->build($mail));
    }

    /**
     * Compile mail template with injected variables.
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return string
     */
    public function render(Mail $mail): string
    {
        return $this->build($mail)->getHtmlBody();
    }

    /**
     * Convert Mail instance to Swift_Message instance
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return \Symfony\Component\Mime\Email
     */
    private function build(Mail $mail): Email
    {
        $message = $mail->build()->render($this->views)->getEmailMessage();

        if (count($message->getFrom()) === 0) {
            $message = $message->from(new Address($this->defaultFromAddress, $this->defaultFromName));
        }

        return $message;
    }
}
