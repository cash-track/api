<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Service\Metrics\AppMetricsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Spiral\Scheduler\Job\CallbackJob;
use Spiral\Scheduler\JobRegistryInterface;
use Tests\DatabaseTransaction;
use Tests\TestCase;

/**
 * MetricsBootloader must register the periodic user-gauge refresh as a scheduler callback,
 * and running that callback must drive AppUserMetricsRefresher against the real repository.
 */
final class UserGaugeScheduleTest extends TestCase implements DatabaseTransaction
{
    public function testRefreshCallbackIsRegisteredAndPopulatesUserGauges(): void
    {
        /** @var AppMetricsInterface&MockObject $metrics */
        $metrics = $this->getMockBuilder(AppMetricsInterface::class)->getMock();
        $this->getContainer()->bind(AppMetricsInterface::class, static fn () => $metrics);

        $metrics->expects($this->exactly(2))
            ->method('setUserCount')
            ->with(
                $this->logicalOr('verified', 'unverified'),
                $this->greaterThanOrEqual(0),
            );
        $metrics->expects($this->exactly(3))
            ->method('setActiveUserCount')
            ->with(
                $this->logicalOr('daily', 'weekly', 'monthly'),
                $this->greaterThanOrEqual(0),
            );

        $job = $this->refreshCallback();

        $this->assertNotNull($job, 'The refresh-app-user-metrics callback job is not registered');

        $job->run($this->getContainer());
    }

    private function refreshCallback(): ?CallbackJob
    {
        $registry = $this->getContainer()->get(JobRegistryInterface::class);

        foreach ($registry->getJobs() as $job) {
            if ($job instanceof CallbackJob && $job->getDescription() === 'refresh-app-user-metrics') {
                return $job;
            }
        }

        return null;
    }
}
