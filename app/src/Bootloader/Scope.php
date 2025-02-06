<?php

declare(strict_types=1);

namespace App\Bootloader;

enum Scope: string
{
    case Http = 'http';
    case HttpRequest = 'http-request';
}
