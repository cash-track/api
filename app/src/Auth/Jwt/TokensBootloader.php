<?php

declare(strict_types=1);

namespace App\Auth\Jwt;

use Spiral\Auth\TokenStorageInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;

class TokensBootloader extends Bootloader
{
    protected const DEPENDENCIES = [
        HttpAuthBootloader::class,
    ];

    protected const SINGLETONS = [
        TokenStorageInterface::class => TokenStorage::class
    ];
}
