<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use App\Repository\UserRepository;

/**
 * Recomputes the periodically-sampled user gauges from the database and pushes them to the
 * metrics backend: `app_users{state}` (by e-mail confirmation) and `app_users_active{window}`
 * (DAU/WAU/MAU from `users.active_at`). Invoked from the metrics scheduler job every 5 minutes.
 */
final class AppUserMetricsRefresher
{
    /**
     * Rolling active-user windows: metric label => window length in days. One `now`
     * reference is shared across all three so the samples line up.
     *
     * @var array<non-empty-string, int<1, max>>
     */
    private const ACTIVE_WINDOWS = [
        AppMetrics::ACTIVE_WINDOW_DAILY => 1,
        AppMetrics::ACTIVE_WINDOW_WEEKLY => 7,
        AppMetrics::ACTIVE_WINDOW_MONTHLY => 30,
    ];

    public function __construct(
        private readonly AppMetricsInterface $metrics,
        private readonly UserRepository $users,
    ) {
    }

    public function refresh(): void
    {
        $this->metrics->setUserCount('verified', $this->users->countByEmailConfirmed(true));
        $this->metrics->setUserCount('unverified', $this->users->countByEmailConfirmed(false));

        $now = new \DateTimeImmutable();

        foreach (self::ACTIVE_WINDOWS as $window => $days) {
            $since = $now->sub(new \DateInterval(sprintf('P%dD', $days)));
            $this->metrics->setActiveUserCount($window, $this->users->countActiveSince($since));
        }
    }
}
