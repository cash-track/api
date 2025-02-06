<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\MailConfig;
use App\Service\Mailer\Mailer;
use App\Service\Mailer\MailerInterface;
use App\Service\UserOptionsService;
use Cycle\ORM\ORMInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Queue\QueueInterface;
use Spiral\Translator\Config\TranslatorConfig;
use Spiral\Translator\Translator;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;

class MailerBootloader extends Bootloader
{
    public function __construct(private readonly MailConfig $config)
    {
    }

    public function boot(Container $container): void
    {
        $container->bind(MailerInterface::class, function (
            ViewsInterface $views,
            QueueInterface $queue,
            ORMInterface $orm,
            Translator $translator,
            TranslatorConfig $translatorConfig,
            UserOptionsService $userOptionsService,
        ): MailerInterface {
            $mailer = new Mailer(
                new SymfonyMailer($this->getTransport()),
                $views,
                $queue,
                $orm,
                $translator,
                $translatorConfig,
                $userOptionsService,
            );

            $mailer->setDefaultFromName($this->config->getSenderName());
            $mailer->setDefaultFromAddress($this->config->getSenderAddress());

            return $mailer;
        });
    }

    private function getTransport(): Transport\TransportInterface
    {
        switch ($this->config->getDriver()) {
            case MailConfig::DRIVER_SMTP:
                return $this->getSmtpTransport();
        }

        throw new \RuntimeException('Unknown mail driver');
    }

    private function getSmtpTransport(): Transport\TransportInterface
    {
        $transport = new Transport\Smtp\EsmtpTransport(
            host: $this->config->getSmtpHost(),
            port: (int) $this->config->getSmtpPort(),
        );

        $transport->setUsername($this->config->getSmtpUsername());
        $transport->setPassword($this->config->getSmtpPassword());

        return $transport;
    }
}
