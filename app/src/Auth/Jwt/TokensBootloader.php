<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use App\Auth\RefreshTokenStorageInterface;
use Spiral\Auth\TokenStorageInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;

final class TokensBootloader extends Bootloader
{
    protected const array DEPENDENCIES = [
        HttpAuthBootloader::class,
    ];

    protected const array SINGLETONS = [
        TokenStorageInterface::class => TokenStorage::class,
        RefreshTokenStorageInterface::class => RefreshTokenStorage::class,
    ];
}
