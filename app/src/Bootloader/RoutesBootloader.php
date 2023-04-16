<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Middleware\LocaleSelectorMiddleware;
use App\Middleware\UserLocaleSelectorMiddleware;
use App\Request\JsonErrorsRenderer;
use Spiral\Bootloader\Http\RoutesBootloader as BaseRoutesBootloader;
use App\Auth\AuthMiddleware;
use Spiral\Auth\Middleware\AuthMiddleware as InitAuthMiddleware;
use Spiral\Debug\StateCollector\HttpCollector;
use Spiral\Filter\ValidationHandlerMiddleware;
use Spiral\Filters\ErrorsRendererInterface;
use Spiral\Http\Middleware\ErrorHandlerMiddleware;
use Spiral\Http\Middleware\JsonPayloadMiddleware;
use Spiral\Router\Bootloader\AnnotatedRoutesBootloader;
use Spiral\Router\Loader\Configurator\RoutingConfigurator;

final class RoutesBootloader extends BaseRoutesBootloader
{
    protected const SINGLETONS = [
        ErrorsRendererInterface::class => JsonErrorsRenderer::class,
    ];

    protected const DEPENDENCIES = [
        AnnotatedRoutesBootloader::class,
    ];

    protected function globalMiddleware(): array
    {
        return [
            LocaleSelectorMiddleware::class,
            ErrorHandlerMiddleware::class,
            JsonPayloadMiddleware::class,
            HttpCollector::class,
            InitAuthMiddleware::class,
            ValidationHandlerMiddleware::class,
        ];
    }

    protected function middlewareGroups(): array
    {
        return [
            'auth' => [
                AuthMiddleware::class,
                UserLocaleSelectorMiddleware::class,
            ],
        ];
    }

    protected function defineRoutes(RoutingConfigurator $routes): void
    {
        //
    }
}
