<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\TraceIdMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\TestCase;

class TraceIdMiddlewareTest extends TestCase
{
    public function testHeaderPresentWhenSpanIsActive(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $response = $this->process(new JsonResponse([], 200));
        } finally {
            $scope->detach();
        }

        $this->assertSame(str_repeat('a', 32), $response->getHeaderLine('X-Ct-Trace-Id'));
    }

    public function testHeaderAbsentWhenNoActiveSpan(): void
    {
        $response = $this->process(new JsonResponse([], 200));

        $this->assertFalse($response->hasHeader('X-Ct-Trace-Id'));
    }

    public function testHeaderStampedOnAnErrorResponseReturnedByTheNextHandler(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $response = $this->process(new JsonResponse(['message' => 'Not Found'], 404));
        } finally {
            $scope->detach();
        }

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(str_repeat('a', 32), $response->getHeaderLine('X-Ct-Trace-Id'));
    }

    private function process(ResponseInterface $handlerResponse): ResponseInterface
    {
        $middleware = new TraceIdMiddleware();

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn($handlerResponse);

        return $middleware->process($request, $handler);
    }
}
