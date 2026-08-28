<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * PSR-3 logger that Cycle's driver writes every executed statement to. It never stores or
 * re-logs the SQL text itself — it only reads the `elapsed` timing Cycle puts in the context
 * to feed the query counter/histogram, then forwards the record untouched to the real
 * database log channel.
 */
final class DatabaseMetricsLogger extends AbstractLogger
{
    public function __construct(
        private readonly LoggerInterface $inner,
        private readonly AppMetricsInterface $metrics,
    ) {
    }

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<array-key, mixed> $context
     */
    #[\Override]
    public function log($level, $message, array $context = []): void
    {
        // Cycle adds `elapsed` (seconds, float) only to per-statement log lines; transaction
        // and savepoint lines carry no timing and are ignored here.
        if (array_key_exists('elapsed', $context) && is_numeric($context['elapsed'])) {
            $this->metrics->observeDatabaseQuery((float) $context['elapsed']);
        }

        $this->inner->log($level, $message, $context);
    }
}
