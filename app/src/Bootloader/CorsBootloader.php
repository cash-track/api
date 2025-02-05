<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Middleware\CorsMiddleware;
use App\Service\Cors\CorsInterface;
use App\Service\Cors\CorsService;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Bootloader\Http\HttpBootloader;

class CorsBootloader extends Bootloader
{
    protected const array BINDINGS = [
        CorsInterface::class => CorsService::class,
    ];

    /**
     * @param \Spiral\Bootloader\Http\HttpBootloader $http
     */
    public function boot(HttpBootloader $http): void
    {
        $http->addMiddleware(CorsMiddleware::class);
    }
}
