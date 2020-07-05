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
     * @var \Spiral\Router\RouterInterface
     */
    private $router;

    /**
     * UriService constructor.
     *
     * @param \App\Config\AppConfig $config
     * @param \Spiral\Router\RouterInterface $router
     */
    public function __construct(AppConfig $config, RouterInterface $router)
    {
        $this->config = $config;
        $this->router = $router;
    }

    /**
     * Get URI to view wallet
     *
     * @param \App\Database\Wallet $wallet
     * @return string
     */
    public function wallet(Wallet $wallet): string
    {
        return $this->home((string) $this->router->uri('wallet.index', ['id' => $wallet->id]));
    }

    /**
     * Get URI to confirm email by token
     *
     * @param string $token
     * @return string
     */
    public function emailConfirmation(string $token): string
    {
        return $this->home((string) $this->router->uri('auth.email.confirm', ['token' => $token]));
    }

    /**
     * Get URI to reset password by code
     *
     * @param string $code
     * @return string
     */
    public function passwordReset(string $code): string
    {
        // TODO. Implement this route on the frontend side. Render password reset form.

        return $this->home("/auth/password/reset/{$code}");
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
