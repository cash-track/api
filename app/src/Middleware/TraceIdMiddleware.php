<?php

declare(strict_types=1);

namespace App\Middleware;

use OpenTelemetry\API\Trace\Span;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stamps every response with the active OpenTelemetry trace ID, so a direct caller can
 * correlate it with the API logs (which carry trace_id via TraceIdProcessor).
 *
 * Reads the SDK's active span directly, not Spiral's TracerInterface wrapper — see the
 * TraceIdProcessor docblock for why.
 */
final class TraceIdMiddleware implements MiddlewareInterface
{
    private const string HEADER_TRACE_ID = 'X-Ct-Trace-Id';

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $context = Span::getCurrent()->getContext();

        if (!$context->isValid()) {
            return $response;
        }

        return $response->withHeader(self::HEADER_TRACE_ID, $context->getTraceId());
    }
}
