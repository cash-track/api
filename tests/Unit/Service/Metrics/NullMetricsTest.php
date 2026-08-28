<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\NullMetrics;
use PHPUnit\Framework\MockObject\MockObject;
use Spiral\RoadRunner\Metrics\CollectorInterface;
use Tests\TestCase;

final class NullMetricsTest extends TestCase
{
    public function testEveryOperationIsANoOpAndNeverThrows(): void
    {
        $metrics = new NullMetrics();
        /** @var CollectorInterface&MockObject $collector */
        $collector = $this->getMockBuilder(CollectorInterface::class)->getMock();

        $metrics->add('app_x', 1.0, ['a']);
        $metrics->sub('app_x', 1.0, ['a']);
        $metrics->observe('app_x', 0.5, ['a']);
        $metrics->set('app_x', 3.0, ['a']);
        $metrics->declare('app_x', $collector);
        $metrics->unregister('app_x');

        $this->expectNotToPerformAssertions();
    }
}
