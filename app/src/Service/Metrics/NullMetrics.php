<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use Spiral\RoadRunner\Metrics\CollectorInterface;
use Spiral\RoadRunner\Metrics\MetricsInterface;

/**
 * No-op metrics implementation used when the RoadRunner metrics RPC is unavailable
 * (e.g. the test environment, where there is no RoadRunner server behind the RPC relay).
 */
final class NullMetrics implements MetricsInterface
{
    #[\Override]
    public function add(string $name, float $value, array $labels = []): void
    {
    }

    #[\Override]
    public function sub(string $name, float $value, array $labels = []): void
    {
    }

    #[\Override]
    public function observe(string $name, float $value, array $labels = []): void
    {
    }

    #[\Override]
    public function set(string $name, float $value, array $labels = []): void
    {
    }

    #[\Override]
    public function declare(string $name, CollectorInterface $collector): void
    {
    }

    #[\Override]
    public function unregister(string $name): void
    {
    }
}
