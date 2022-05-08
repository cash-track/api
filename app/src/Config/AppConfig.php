<?php

declare(strict_types=1);

namespace App\Config;

use Spiral\Core\InjectableConfig;

class AppConfig extends InjectableConfig
{
    public const CONFIG = 'app';

    /**
     * @internal For internal usage. Will be hydrated in the constructor.
     */
    protected $config = [
        'url' => '',
        'website_url' => '',
        'web_app_url' => '',

        'email_confirmation_link' => '',
        'password_reset_link' => '',
        'wallet_link' => '',
    ];

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->config['url'];
    }

    /**
     * @return string
     */
    public function getWebSiteUrl(): string
    {
        return $this->config['website_url'];
    }

    /**
     * @return string
     */
    public function getWebAppUrl(): string
    {
        return $this->config['web_app_url'];
    }

    /**
     * @param string $token
     * @return string
     */
    public function getEmailConfirmationLink(string $token): string
    {
        return str_replace('{token}', $token, $this->config['email_confirmation_link']);
    }

    /**
     * @param string $code
     * @return string
     */
    public function getPasswordResetLink(string $code): string
    {
        return str_replace('{code}', $code, $this->config['password_reset_link']);
    }

    /**
     * @param int $walletId
     * @return string
     */
    public function getWalletLink(int $walletId): string
    {
        return str_replace('{wallet}', (string) $walletId, $this->config['wallet_link']);
    }
}
