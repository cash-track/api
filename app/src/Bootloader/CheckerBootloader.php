<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Security\PasswordChecker;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Security\ValidationBootloader;

class CheckerBootloader extends Bootloader
{
    /**
     * @param \Spiral\Bootloader\Security\ValidationBootloader $validation
     */
    public function boot(ValidationBootloader $validation): void
    {
        $validation->addChecker('password', PasswordChecker::class);
    }
}
