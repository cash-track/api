<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\AppMetricsInterface;
use App\Service\Metrics\DatabaseMetricsLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

final class DatabaseMetricsLoggerTest extends TestCase
{
    private LoggerInterface&MockObject $inner;
    private AppMetricsInterface&MockObject $metrics;
    private DatabaseMetricsLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->metrics = $this->getMockBuilder(AppMetricsInterface::class)->getMock();
        $this->logger = new DatabaseMetricsLogger($this->inner, $this->metrics);
    }

    public function testObservesQueryDurationWhenElapsedIsPresent(): void
    {
        $this->metrics->expects($this->once())
            ->method('observeDatabaseQuery')
            ->with(0.0125);

        $this->inner->expects($this->once())
            ->method('log')
            ->with('info', 'SELECT 1', ['elapsed' => 0.0125]);

        $this->logger->log('info', 'SELECT 1', ['elapsed' => 0.0125]);
    }

    public function testDoesNotObserveWhenElapsedIsMissing(): void
    {
        $this->metrics->expects($this->never())->method('observeDatabaseQuery');

        $this->inner->expects($this->once())
            ->method('log')
            ->with('info', 'BEGIN TRANSACTION', []);

        $this->logger->log('info', 'BEGIN TRANSACTION', []);
    }

    public function testDoesNotObserveWhenElapsedIsNotNumeric(): void
    {
        $this->metrics->expects($this->never())->method('observeDatabaseQuery');
        $this->inner->expects($this->once())->method('log');

        $this->logger->log('info', 'SELECT 1', ['elapsed' => 'n/a']);
    }

    public function testAlwaysForwardsRecordToInnerLoggerEvenWhenObserving(): void
    {
        $this->metrics->method('observeDatabaseQuery');

        $this->inner->expects($this->once())
            ->method('log')
            ->with('debug', 'SELECT 2', ['elapsed' => 0.5, 'extra' => 'kept']);

        $this->logger->log('debug', 'SELECT 2', ['elapsed' => 0.5, 'extra' => 'kept']);
    }
}
