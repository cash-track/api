<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\LoggerFactoryInterface;
use Psr\Log\LoggerInterface;
use Spiral\Cycle\LoggerFactory;

/**
 * Decorates Spiral's Cycle logger factory so every database driver logs through
 * {@see DatabaseMetricsLogger}. Keeps the framework's per-driver Monolog channel resolution
 * intact and only layers metric observation on top of it.
 */
final class DatabaseMetricsLoggerFactory implements LoggerFactoryInterface
{
    public function __construct(
        private readonly LoggerFactory $inner,
        private readonly AppMetricsInterface $metrics,
    ) {
    }

    #[\Override]
    public function getLogger(?DriverInterface $driver = null): LoggerInterface
    {
        return new DatabaseMetricsLogger($this->inner->getLogger($driver), $this->metrics);
    }
}
