<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Metrics;

use App\Database\Charge;
use App\Service\Metrics\AppMetrics;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Metrics\MetricsInterface;
use Tests\TestCase;

final class AppMetricsTest extends TestCase
{
    private MetricsInterface&MockObject $rr;
    private LoggerInterface&MockObject $logger;
    private AppMetrics $metrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rr = $this->getMockBuilder(MetricsInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->metrics = new AppMetrics($this->rr, $this->logger);
    }

    public function testDefinitionsCoverEveryEmittedMetricName(): void
    {
        $names = array_keys(AppMetrics::definitions());

        $this->assertContains('app_db_queries_total', $names);
        $this->assertContains('app_db_query_duration_seconds', $names);
        $this->assertContains('app_auth_logins_total', $names);
        $this->assertContains('app_auth_registrations_total', $names);
        $this->assertContains('app_users', $names);
        $this->assertContains('app_users_active', $names);
        $this->assertContains('app_charges_created_total', $names);
        $this->assertContains('app_charges_deleted_total', $names);
        $this->assertContains('app_tag_assignments_total', $names);
        $this->assertContains('app_wallets_created_total', $names);
        $this->assertContains('app_wallets_archived_total', $names);
        $this->assertContains('app_tags_created_total', $names);

        // lower_snake, app_-prefixed.
        foreach ($names as $name) {
            $this->assertMatchesRegularExpression('/^app_[a-z0-9_]+$/', $name);
        }
    }

    public function testObserveDatabaseQueryBumpsCounterAndHistogram(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_db_queries_total', 1.0, []);
        $this->rr->expects($this->once())
            ->method('observe')
            ->with('app_db_query_duration_seconds', 0.021, []);

        $this->metrics->observeDatabaseQuery(0.021);
    }

    public function testIncrementLoginResolvesMethodAndResultLabels(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_auth_logins_total', 1.0, ['password', 'failure']);

        $this->metrics->incrementLogin('password', false);
    }

    public function testIncrementLoginSuccessLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_auth_logins_total', 1.0, ['google', 'success']);

        $this->metrics->incrementLogin('google', true);
    }

    public function testIncrementRegistration(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_auth_registrations_total', 1.0, ['success']);

        $this->metrics->incrementRegistration(true);
    }

    public function testSetUserCountUsesGaugeWithStateLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('set')
            ->with('app_users', 42.0, ['verified']);

        $this->metrics->setUserCount('verified', 42);
    }

    public function testSetActiveUserCountUsesGaugeWithWindowLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('set')
            ->with('app_users_active', 17.0, ['weekly']);

        $this->metrics->setActiveUserCount('weekly', 17);
    }

    public function testSetActiveUserCountRejectsUnknownWindowAndLogs(): void
    {
        $this->rr->expects($this->never())->method('set');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to emit application metric', $this->callback(function (array $context): bool {
                $this->assertSame('users_active', $context['metric']);
                $this->assertStringContainsString('yearly', $context['error']);

                return true;
            }));

        $this->metrics->setActiveUserCount('yearly', 5);
    }

    public function testIncrementChargeCreatedMapsRawTypeToLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_charges_created_total', 1.0, ['income']);

        $this->metrics->incrementChargeCreated(Charge::TYPE_INCOME);
    }

    public function testIncrementChargeDeletedMapsRawTypeToLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_charges_deleted_total', 1.0, ['expense']);

        $this->metrics->incrementChargeDeleted(Charge::TYPE_EXPENSE);
    }

    public function testIncrementChargeCreatedUnknownTypeFallsBackToBoundedLabel(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_charges_created_total', 1.0, ['unknown']);

        $this->metrics->incrementChargeCreated('?');
    }

    public function testIncrementTagAssignmentsAddsTheGivenCount(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_tag_assignments_total', 3.0, []);

        $this->metrics->incrementTagAssignments(3);
    }

    public function testIncrementTagAssignmentsIsNoOpForNonPositiveCount(): void
    {
        $this->rr->expects($this->never())->method('add');

        $this->metrics->incrementTagAssignments(0);
    }

    public function testIncrementWalletCreated(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_wallets_created_total', 1.0, []);

        $this->metrics->incrementWalletCreated();
    }

    public function testIncrementWalletArchived(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_wallets_archived_total', 1.0, []);

        $this->metrics->incrementWalletArchived();
    }

    public function testIncrementTagCreated(): void
    {
        $this->rr->expects($this->once())
            ->method('add')
            ->with('app_tags_created_total', 1.0, []);

        $this->metrics->incrementTagCreated();
    }

    public function testMetricsFailureIsSwallowedAndLogged(): void
    {
        $this->rr->method('add')->willThrowException(new \RuntimeException('rpc down'));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to emit application metric', $this->callback(function (array $context): bool {
                $this->assertSame('auth_logins_total', $context['metric']);
                $this->assertSame('rpc down', $context['error']);

                return true;
            }));

        // Must not throw.
        $this->metrics->incrementLogin('password', true);
    }
}
