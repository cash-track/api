<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class MailConfig extends InjectableConfig
{
    public const CONFIG = 'mail';
    public const DRIVER_SMTP = 'smtp';

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

    /**
     * @return string
     */
    public function getSenderName(): string
    {
        return (string) $this->config['sender']['name'];
    }

    /**
     * @return string
     */
    public function getSenderAddress(): string
    {
        return (string) $this->config['sender']['address'];
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->config['driver'];
    }

    /**
     * @return string
     */
    public function getSmtpHost(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['host'];
    }

    /**
     * @return string
     */
    public function getSmtpPort(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['port'];
    }

    /**
     * @return string
     */
    public function getSmtpUsername(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['username'];
    }

    /**
     * @return string
     */
    public function getSmtpPassword(): string
    {
        return (string) $this->config['drivers'][self::DRIVER_SMTP]['password'];
    }
}
