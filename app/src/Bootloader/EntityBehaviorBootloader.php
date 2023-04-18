<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Database\Encrypter\Encrypter;
use App\Database\Encrypter\EncrypterInterface;
use Cycle\ORM\Transaction\CommandGeneratorInterface;
use Cycle\ORM\Entity\Behavior\EventDrivenCommandGenerator;
use Spiral\Boot\Bootloader\Bootloader;

final class EntityBehaviorBootloader extends Bootloader
{
    protected const BINDINGS = [
        CommandGeneratorInterface::class => EventDrivenCommandGenerator::class,
        EncrypterInterface::class => Encrypter::class,
    ];
}
