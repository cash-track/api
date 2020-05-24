<?php

declare(strict_types=1);

namespace App\Auth;

use Spiral\Auth\Transport\HeaderTransport;
use Spiral\Boot\Bootloader\Bootloader as FrameworkBootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;

class Bootloader extends FrameworkBootloader
{
    /**
     * @param \Spiral\Bootloader\Auth\HttpAuthBootloader $auth
     */
    public function boot(HttpAuthBootloader $auth): void
    {
        $auth->addTransport('auth-header', new HeaderTransport('Authorization'));
    }
}
