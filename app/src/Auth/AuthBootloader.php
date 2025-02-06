<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Jwt\TokenStorage;
use App\Bootloader\Scope;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Boot\Bootloader\Bootloader as FrameworkBootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;
use Spiral\Core\BinderInterface;

class AuthBootloader extends FrameworkBootloader
{
    public function boot(HttpAuthBootloader $auth): void
    {
        $auth->addTransport(BearerHeaderTransport::TRANSPORT, new BearerHeaderTransport());
    }

    public function init(BinderInterface $binder): void
    {
        $httpBinder = $binder->getBinder(Scope::Http);
        $httpBinder->bindSingleton(TokenStorageInterface::class, TokenStorage::class);
    }
}
