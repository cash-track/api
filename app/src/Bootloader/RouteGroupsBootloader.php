<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Auth\AuthMiddleware;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Router\GroupRegistry;

class RouteGroupsBootloader extends Bootloader
{
    /**
     * @param \Spiral\Router\GroupRegistry $groups
     */
    public function boot(GroupRegistry $groups): void
    {
        $groups->getGroup('auth')->addMiddleware(AuthMiddleware::class);
    }
}
