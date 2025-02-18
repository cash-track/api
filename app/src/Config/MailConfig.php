<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class MailConfig extends InjectableConfig
{
    public const string CONFIG = 'mail';
    public const string DRIVER_SMTP = 'smtp';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected array $config = [
        'sender' => [
            'name'    => null,
            'address' => null,
        ],
        'driver'  => '',
        'drivers' => [
            self::DRIVER_SMTP => [
                'host'       => null,
                'port'       => null,
                'username'   => null,
                'password'   => null,
                'encryption' => null,
            ],
        ],
    ];

    public function getSenderName(): string
    {
        return (string) $this->config['sender']['name'];
    }

    public function getSenderAddress(): string
    {
        return (string) $this->config['sender']['address'];
    }

    public function getDriver(): string
    {
        return $this->config['driver'];
    }

    public function getSmtpHost(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['host'];
    }

    public function getSmtpPort(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['port'];
    }

    public function getSmtpUsername(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['username'];
    }

    public function getSmtpPassword(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['password'];
    }
}
