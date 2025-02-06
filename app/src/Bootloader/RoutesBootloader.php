<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Auth\AuthMiddleware;
use App\Middleware\LocaleSelectorMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\UserLocaleSelectorMiddleware;
use App\Request\JsonErrorsRenderer;
use App\Service\RateLimit\RateLimitInterface;
use App\Service\RateLimit\RedisRateLimit;
use Spiral\Auth\Middleware\AuthMiddleware as InitAuthMiddleware;
use Spiral\Bootloader\Http\RoutesBootloader as BaseRoutesBootloader;
use Spiral\Debug\StateCollector\HttpCollector;
use Spiral\Filter\ValidationHandlerMiddleware;
use Spiral\Filters\ErrorsRendererInterface;
use Spiral\Http\Middleware\ErrorHandlerMiddleware;
use Spiral\Http\Middleware\JsonPayloadMiddleware;
use Spiral\Router\Bootloader\AnnotatedRoutesBootloader;
use Spiral\Router\Loader\Configurator\RoutingConfigurator;

final class RoutesBootloader extends BaseRoutesBootloader
{
    protected const array SINGLETONS = [
        ErrorsRendererInterface::class => JsonErrorsRenderer::class,
    ];

    protected const array BINDINGS = [
        RateLimitInterface::class => RedisRateLimit::class,
    ];

    protected const array DEPENDENCIES = [
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
                RateLimitMiddleware::class,
                UserLocaleSelectorMiddleware::class,
            ],
            'web' => [
                RateLimitMiddleware::class,
            ],
        ];
    }

    protected function defineRoutes(RoutingConfigurator $routes): void
    {
        //
    }
}
