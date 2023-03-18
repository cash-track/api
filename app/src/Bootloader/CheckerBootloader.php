<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Security\PasswordChecker;
use App\Security\UniqueChecker;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Validator\Bootloader\ValidatorBootloader;

class CheckerBootloader extends Bootloader
{
    /**
     * @param \Spiral\Validator\Bootloader\ValidatorBootloader $validation
     */
    public function boot(ValidatorBootloader $validation): void
    {
        $validation->addChecker('password', PasswordChecker::class);
        $validation->addChecker('unique', UniqueChecker::class);
    }
}
