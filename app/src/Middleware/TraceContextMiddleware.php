<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Telemetry\TraceKind;
use Spiral\Telemetry\TracerFactoryInterface;

final class TraceContextMiddleware implements MiddlewareInterface
{
    public function __construct(protected TracerFactoryInterface $tracerFactory)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tracer = $this->tracerFactory->make($request->getHeaders());

        $response = $tracer->trace(
            name: \sprintf('%s %s', $request->getMethod(), (string) $request->getUri()),
            callback: fn () => $handler->handle($request),
            attributes: [
                'http.method' => $request->getMethod(),
                'http.url' => $request->getUri(),
                'http.headers' => $request->getHeaders(),
            ],
            scoped: true,
            traceKind: TraceKind::SERVER
        );

        return $response;
    }
}
