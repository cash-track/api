<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use Spiral\Testing\Attribute\Env;
use Tests\TestCase;

/**
 * Hand-activated span: pins the middleware itself. #[Env('TELEMETRY_DRIVER', 'otel')]: pins
 * that TraceContextMiddleware really activates a span this middleware can read.
 */
class TraceIdMiddlewareTest extends TestCase
{
    public function testHeaderPresentOnSuccessResponseWhenSpanIsActive(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $response = $this->get('/healthcheck');
        } finally {
            $scope->detach();
        }

        $response->assertOk()
            ->assertHasHeader('X-Ct-Trace-Id', str_repeat('a', 32));
    }

    // Pins the ordering: after ErrorHandlerMiddleware this response would never be seen.
    public function testHeaderPresentOnErrorResponseWhenSpanIsActive(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $response = $this->get('/this-route-does-not-exist');
        } finally {
            $scope->detach();
        }

        $response->assertStatus(404)
            ->assertHasHeader('X-Ct-Trace-Id', str_repeat('a', 32));
    }

    public function testHeaderOmittedWhenNoActiveSpan(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk()
            ->assertHeaderMissing('X-Ct-Trace-Id');
    }

    #[Env('TELEMETRY_DRIVER', 'otel')]
    public function testHeaderPresentWithValidTraceIdOnSuccess(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk();
        $traceId = $response->getOriginalResponse()->getHeaderLine('X-Ct-Trace-Id');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $traceId);
    }

    #[Env('TELEMETRY_DRIVER', 'otel')]
    public function testHeaderPresentWithValidTraceIdOnError(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);
        $traceId = $response->getOriginalResponse()->getHeaderLine('X-Ct-Trace-Id');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $traceId);
    }
}
