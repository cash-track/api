<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\AppConfig;
use App\Database\Wallet;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Router\RouterInterface;

/**
 * @Prototyped(property="uriService")
 */
class UriService
{
    /**
     * @var \App\Config\AppConfig
     */
    private $config;

    /**
     * UriService constructor.
     *
     * @param \App\Config\AppConfig $config
     */
    public function __construct(AppConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Get URI to view wallet
     *
     * @param \App\Database\Wallet $wallet
     * @return string
     */
    public function wallet(Wallet $wallet): string
    {
        return $this->config->getWebAppUrl() . $this->config->getWalletLink($wallet->id);
    }

    /**
     * Get URI to confirm email by token
     *
     * @param string $token
     * @return string
     */
    public function emailConfirmation(string $token): string
    {
        return $this->config->getWebSiteUrl() . $this->config->getEmailConfirmationLink($token);
    }

    /**
     * Get URI to reset password by code
     *
     * @param string $code
     * @return string
     */
    public function passwordReset(string $code): string
    {
        return $this->config->getWebSiteUrl() . $this->config->getPasswordResetLink($code);
    }

    /**
     * Get home URI
     *
     * @param string $path
     * @return string
     */
    public function home(string $path = ''): string
    {
        return $this->config->getUrl() . $path;
    }
}
