<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Config\MailConfig;
use App\Service\Mailer\Mailer;
use App\Service\Mailer\MailerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Views\ViewsInterface;

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
            $mailer = new Mailer(new \Swift_Mailer($this->getTransport()), $views);

            return $mailer->setDefaultFromName($this->config->getSenderName())
                          ->setDefaultFromAddress($this->config->getSenderAddress());
        });
    }

    /**
     * @return \Swift_Transport
     */
    private function getTransport(): \Swift_Transport
    {
        switch ($this->config->getDriver()) {
            case MailConfig::DRIVER_SMTP:
                return $this->getSmtpTransport();
        }

        throw new \RuntimeException('Unknown mail driver');
    }

    /**
     * @return \Swift_SmtpTransport
     */
    private function getSmtpTransport(): \Swift_SmtpTransport
    {
        $transport = new \Swift_SmtpTransport();

        return $transport->setHost($this->config->getSmtpHost())
                         ->setPort($this->config->getSmtpPort())
                         ->setUsername($this->config->getSmtpUsername())
                         ->setPassword($this->config->getSmtpPassword())
                         ->setEncryption($this->config->getSmtpEncryption());
    }
}
