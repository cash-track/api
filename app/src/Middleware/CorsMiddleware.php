<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\Cors\CorsInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(protected CorsInterface $service)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->service->isPreflightRequest($request)) {
            return $this->service->handlePreflightRequest($request);
        }

        $response = $handler->handle($request);

        return $this->service->handleActualRequest($request, $response);
    }
}
