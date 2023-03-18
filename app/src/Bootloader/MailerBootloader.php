<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\MailConfig;
use App\Service\Mailer\Mailer;
use App\Service\Mailer\MailerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Views\ViewsInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;

class MailerBootloader extends Bootloader
{
    /**
     * @var \App\Config\MailConfig
     */
    private $config;

    /**
     * FirebaseBootloader constructor.
     *
     * @param \App\Config\MailConfig $config
     */
    public function __construct(MailConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Spiral\Core\Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
        $container->bind(MailerInterface::class, function (ViewsInterface $views): MailerInterface {
            $mailer = new Mailer(new SymfonyMailer($this->getTransport()), $views);

            $mailer->setDefaultFromName($this->config->getSenderName());
            $mailer->setDefaultFromAddress($this->config->getSenderAddress());

            return $mailer;
        });
    }

    /**
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    private function getTransport(): Transport\TransportInterface
    {
        switch ($this->config->getDriver()) {
            case MailConfig::DRIVER_SMTP:
                return $this->getSmtpTransport();
        }

        throw new \RuntimeException('Unknown mail driver');
    }

    /**
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
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
