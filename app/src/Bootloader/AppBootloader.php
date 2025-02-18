<?php

declare(strict_types=1);

namespace App\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\Slugger\SluggerInterface;

final class AppBootloader extends Bootloader
{
    protected const array BINDINGS = [
        SluggerInterface::class => AsciiSlugger::class,
    ];
}
