<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Metrics;

use App\Repository\UserRepository;
use App\Service\Metrics\AppMetricsInterface;
use App\Service\Metrics\AppUserMetricsRefresher;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class AppUserMetricsRefresherTest extends TestCase
{
    private AppMetricsInterface&MockObject $metrics;
    private UserRepository&MockObject $users;
    private AppUserMetricsRefresher $refresher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metrics = $this->getMockBuilder(AppMetricsInterface::class)->getMock();
        $this->users = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['countByEmailConfirmed', 'countActiveSince'])
            ->getMock();

        $this->refresher = new AppUserMetricsRefresher($this->metrics, $this->users);
    }

    public function testRefreshPushesUserStateGaugesFromRepositoryCounts(): void
    {
        $this->users->method('countByEmailConfirmed')->willReturnMap([
            [true, 7],
            [false, 3],
        ]);
        $this->users->method('countActiveSince')->willReturn(0);

        $stateCounts = [];
        $this->metrics->method('setUserCount')
            ->willReturnCallback(static function (string $state, int $count) use (&$stateCounts): void {
                $stateCounts[$state] = $count;
            });

        $this->refresher->refresh();

        $this->assertSame(['verified' => 7, 'unverified' => 3], $stateCounts);
    }

    public function testRefreshSetsAllThreeActiveWindowsFromRepositoryCounts(): void
    {
        $this->users->method('countByEmailConfirmed')->willReturn(0);

        $now = new \DateTimeImmutable();
        $windowDays = [];

        $this->users->method('countActiveSince')
            ->willReturnCallback(static function (\DateTimeInterface $since) use ($now, &$windowDays): int {
                $days = (int) round(($now->getTimestamp() - $since->getTimestamp()) / 86400);
                $windowDays[] = $days;

                return match ($days) {
                    1 => 11,
                    7 => 42,
                    30 => 128,
                    default => -1,
                };
            });

        $activeCounts = [];
        $this->metrics->method('setActiveUserCount')
            ->willReturnCallback(static function (string $window, int $count) use (&$activeCounts): void {
                $activeCounts[$window] = $count;
            });

        $this->refresher->refresh();

        $this->assertSame([1, 7, 30], $windowDays, 'one shared now, windows 1d/7d/30d back');
        $this->assertSame(
            ['daily' => 11, 'weekly' => 42, 'monthly' => 128],
            $activeCounts,
        );
    }
}
