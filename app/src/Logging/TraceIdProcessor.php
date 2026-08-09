<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

/**
 * Injects the active OpenTelemetry span's trace_id into every log record.
 *
 * Reads the SDK's real active span directly instead of Spiral's TracerInterface
 * wrapper, whose default binding resolves a fresh Tracer instance per call and
 * never sees the activated span (see vendor/spiral/otel-bridge Tracer::getContext()).
 */
final class TraceIdProcessor implements ProcessorInterface
{
    /**
     * ProcessorInterface only declares `@return LogRecord` in its docblock (no enforced return
     * type), but the defensive array-record branch below must be able to return array too —
     * matching the same LogRecord|array widening the vendor TelemetryProcessor uses.
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    #[\Override]
    public function __invoke(LogRecord|array $record): array|LogRecord
    {
        $context = Span::getCurrent()->getContext();

        if (!$context->isValid()) {
            return $record;
        }

        if ($record instanceof LogRecord) {
            $record->extra['trace_id'] = $context->getTraceId();

            return $record;
        }

        $record['extra']['trace_id'] = $context->getTraceId();

        return $record;
    }
}
