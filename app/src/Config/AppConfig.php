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
    protected array $config = [
        'url' => '',
        'website_url' => '',
        'web_app_url' => '',

        'email_confirmation_link' => '',
        'password_reset_link' => '',
        'wallet_link' => '',

        'db_encrypter_key' => '',
    ];

    public function getUrl(): string
    {
        return $this->config['url'];
    }

    public function getWebSiteUrl(): string
    {
        return $this->config['website_url'];
    }

    public function getWebAppUrl(): string
    {
        return $this->config['web_app_url'];
    }

    public function getEmailConfirmationLink(string $token): string
    {
        /** @psalm-suppress PossiblyInvalidCast */
        return (string) str_replace('{token}', $token, $this->config['email_confirmation_link']);
    }

    public function getPasswordResetLink(string $code): string
    {
        /** @psalm-suppress PossiblyInvalidCast */
        return (string) str_replace('{code}', $code, $this->config['password_reset_link']);
    }

    public function getWalletLink(int $walletId): string
    {
        /** @psalm-suppress PossiblyInvalidCast */
        return (string) str_replace('{wallet}', (string) $walletId, $this->config['wallet_link']);
    }

    public function getDbEncrypterKey(): string
    {
        return (string) $this->config['db_encrypter_key'];
    }
}
