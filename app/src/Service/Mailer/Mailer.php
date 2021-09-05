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
     * @param \Swift_Mailer $mailer
     * @param \Spiral\Views\ViewsInterface $views
     */
    public function __construct(\Swift_Mailer $mailer, ViewsInterface $views)
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
        return $this->build($mail)->getBody();
    }

    /**
     * Convert Mail instance to Swift_Message instance
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return \Swift_Message
     */
    private function build(Mail $mail): \Swift_Message
    {
        $message = $mail->build()->render($this->views)->getSwiftMessage();

        if (!is_array($message->getFrom()) || count($message->getFrom()) == 0) {
            $message = $message->addFrom($this->defaultFromAddress, $this->defaultFromName);
        }

        return $message;
    }
}
