<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Repository\UserRepository;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Auth\AuthBootloader;

class UserBootloader extends Bootloader
{
    public function boot(AuthBootloader $auth): void
    {
        $auth->addActorProvider(UserRepository::class);
    }
}
