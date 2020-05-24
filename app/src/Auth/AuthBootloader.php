<?php

declare(strict_types=1);

namespace App\Auth;

use Spiral\Boot\Bootloader\Bootloader as FrameworkBootloader;
use Spiral\Bootloader\Auth\HttpAuthBootloader;

class AuthBootloader extends FrameworkBootloader
{
    /**
     * @param \Spiral\Bootloader\Auth\HttpAuthBootloader $auth
     */
    public function boot(HttpAuthBootloader $auth): void
    {
        $auth->addTransport('bearer-header', new BearerHeaderTransport());
    }
}
