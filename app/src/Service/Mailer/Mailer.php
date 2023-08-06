<?php

declare(strict_types=1);

namespace App\Service\Mailer;

use App\Jobs\SendMailJob;
use App\Mail\UserMail;
use App\Service\UserOptionsService;
use Cycle\ORM\ORMInterface;
use Spiral\Queue\Options;
use Spiral\Queue\QueueInterface;
use Spiral\Translator\Config\TranslatorConfig;
use Spiral\Translator\Translator;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class Mailer implements MailerInterface
{
    const QUEUE_NAME = 'high-priority';

    /**
     * @var string
     */
    private $defaultFromName = '';

    /**
     * @var string
     */
    private $defaultFromAddress = '';

    public function __construct(
        private readonly SymfonyMailerInterface $mailer,
        private readonly ViewsInterface $views,
        private readonly QueueInterface $queue,
        private readonly ORMInterface $orm,
        private readonly Translator $translator,
        private readonly TranslatorConfig $translatorConfig,
        private readonly UserOptionsService $userOptionsService,
    ) {
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
     * Push a queue job to compile, render and send given mail in queue
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function send(Mail $mail): void
    {
        $this->queue->push(SendMailJob::class, $mail->toPayload(), Options::onQueue(self::QUEUE_NAME));
    }

    /**
     * Compile, render and send given mail using previously configured transport.
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return void
     */
    public function sendNow(Mail $mail): void
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
        return (string) $this->build($mail)->getHtmlBody();
    }

    /**
     * Convert Mail instance to Swift_Message instance
     *
     * @param \App\Service\Mailer\Mail $mail
     * @return \Symfony\Component\Mime\Email
     */
    private function build(Mail $mail): Email
    {
        $mail->hydrate($this->orm);

        $this->setLocale($mail);

        $message = $mail->build()->render($this->views)->getEmailMessage();

        if (count($message->getFrom()) === 0) {
            $message = $message->from(new Address($this->defaultFromAddress, $this->defaultFromName));
        }

        return $message;
    }

    private function setLocale(Mail $mail): void
    {
        $this->translator->setLocale($this->translatorConfig->getDefaultLocale());

        if (! $mail instanceof UserMail || $mail->user === null) {
            return;
        }

        $this->translator->setLocale($this->userOptionsService->getLocale($mail->user) ?? $this->translatorConfig->getDefaultLocale());
    }
}
