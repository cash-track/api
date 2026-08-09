<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\TraceIdProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use Tests\TestCase;

class TraceIdProcessorTest extends TestCase
{
    public function testLeavesExtraUntouchedWhenNoActiveSpan(): void
    {
        $processor = new TraceIdProcessor();

        $record = $processor->__invoke($this->makeRecord());

        $this->assertArrayNotHasKey('trace_id', $record->extra);
    }

    public function testAddsTraceIdWhenSpanIsActive(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $processor = new TraceIdProcessor();

            $record = $processor->__invoke($this->makeRecord());

            $this->assertSame(str_repeat('a', 32), $record->extra['trace_id']);
        } finally {
            $scope->detach();
        }
    }

    public function testLeavesArrayRecordExtraUntouchedWhenNoActiveSpan(): void
    {
        $processor = new TraceIdProcessor();

        $record = $processor->__invoke($this->makeArrayRecord());

        $this->assertArrayNotHasKey('trace_id', $record['extra']);
    }

    public function testAddsTraceIdToArrayRecordWhenSpanIsActive(): void
    {
        $context = SpanContext::create(str_repeat('a', 32), str_repeat('b', 16));
        $scope = Span::wrap($context)->activate();

        try {
            $processor = new TraceIdProcessor();

            $record = $processor->__invoke($this->makeArrayRecord());

            $this->assertSame(str_repeat('a', 32), $record['extra']['trace_id']);
        } finally {
            $scope->detach();
        }
    }

    private function makeRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'default',
            level: Level::Info,
            message: 'test message',
        );
    }

    /**
     * Legacy array-shaped record, as accepted by ProcessorInterface historically
     * (Monolog 3.10 itself always builds LogRecord objects, but the processor
     * still declares and handles the array branch defensively).
     *
     * @return array<string, mixed>
     */
    private function makeArrayRecord(): array
    {
        return [
            'message' => 'test message',
            'context' => [],
            'level' => Level::Info->value,
            'level_name' => Level::Info->getName(),
            'channel' => 'default',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];
    }
}
