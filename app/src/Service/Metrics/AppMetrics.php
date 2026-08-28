<?php

declare(strict_types=1);

namespace App\Service\Metrics;

use App\Database\Charge;
use Psr\Log\LoggerInterface;
use Spiral\RoadRunner\Metrics\MetricsInterface;

/**
 * Thin wrapper over the RoadRunner metrics plugin exposing typed, bounded-label methods
 * for application business metrics. The collectors themselves are declared statically in
 * `.rr.yaml` (`metrics.collect:`); {@see self::definitions()} is the code-side mirror of
 * that block, asserted against it by {@see \Tests\Unit\Service\Metrics\MetricsCollectConfigTest}
 * so the YAML and the runtime call sites can never drift apart.
 */
final class AppMetrics implements AppMetricsInterface
{
    public const NAMESPACE = 'app';

    public const DB_QUERIES_TOTAL = 'db_queries_total';
    public const DB_QUERY_DURATION_SECONDS = 'db_query_duration_seconds';
    public const AUTH_LOGINS_TOTAL = 'auth_logins_total';
    public const AUTH_REGISTRATIONS_TOTAL = 'auth_registrations_total';
    public const USERS = 'users';
    public const USERS_ACTIVE = 'users_active';
    public const CHARGES_CREATED_TOTAL = 'charges_created_total';
    public const CHARGES_DELETED_TOTAL = 'charges_deleted_total';
    public const TAG_ASSIGNMENTS_TOTAL = 'tag_assignments_total';
    public const WALLETS_CREATED_TOTAL = 'wallets_created_total';
    public const WALLETS_ARCHIVED_TOTAL = 'wallets_archived_total';
    public const TAGS_CREATED_TOTAL = 'tags_created_total';

    public const ACTIVE_WINDOW_DAILY = 'daily';
    public const ACTIVE_WINDOW_WEEKLY = 'weekly';
    public const ACTIVE_WINDOW_MONTHLY = 'monthly';

    private const RESULT_SUCCESS = 'success';
    private const RESULT_FAILURE = 'failure';

    /**
     * @var list<non-empty-string>
     */
    private const ACTIVE_WINDOWS = [
        self::ACTIVE_WINDOW_DAILY,
        self::ACTIVE_WINDOW_WEEKLY,
        self::ACTIVE_WINDOW_MONTHLY,
    ];

    public function __construct(
        private readonly MetricsInterface $metrics,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Code-side mirror of the `metrics.collect:` block in `.rr.yaml`, keyed by the fully
     * qualified Prometheus series name. Used to address collectors at runtime and to guard
     * the YAML against drift in a test; the RoadRunner plugin is what actually registers
     * them, from the static config, before any worker boots.
     *
     * The `app_` prefix is baked into the name; `namespace`/`subsystem` are deliberately
     * left unset in the YAML. The plugin feeds `namespace` into Prometheus `*Opts.Namespace`
     * and the map key into `*Opts.Name`, and `BuildFQName` joins them with `_` — so setting
     * both would emit a doubled `app_app_*` series. Prefix-in-name keeps the declared name
     * and the emitted series identical.
     *
     * @return array<non-empty-string, array{
     *     type: 'counter'|'gauge'|'histogram',
     *     labels: list<non-empty-string>,
     *     buckets?: non-empty-list<float>
     * }>
     */
    public static function definitions(): array
    {
        $ns = self::NAMESPACE;

        return [
            "{$ns}_" . self::DB_QUERIES_TOTAL => ['type' => 'counter', 'labels' => []],
            "{$ns}_" . self::DB_QUERY_DURATION_SECONDS => [
                'type' => 'histogram',
                'labels' => [],
                'buckets' => [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
            ],
            "{$ns}_" . self::AUTH_LOGINS_TOTAL => ['type' => 'counter', 'labels' => ['method', 'result']],
            "{$ns}_" . self::AUTH_REGISTRATIONS_TOTAL => ['type' => 'counter', 'labels' => ['result']],
            "{$ns}_" . self::USERS => ['type' => 'gauge', 'labels' => ['state']],
            "{$ns}_" . self::USERS_ACTIVE => ['type' => 'gauge', 'labels' => ['window']],
            "{$ns}_" . self::CHARGES_CREATED_TOTAL => ['type' => 'counter', 'labels' => ['type']],
            "{$ns}_" . self::CHARGES_DELETED_TOTAL => ['type' => 'counter', 'labels' => ['type']],
            "{$ns}_" . self::TAG_ASSIGNMENTS_TOTAL => ['type' => 'counter', 'labels' => []],
            "{$ns}_" . self::WALLETS_CREATED_TOTAL => ['type' => 'counter', 'labels' => []],
            "{$ns}_" . self::WALLETS_ARCHIVED_TOTAL => ['type' => 'counter', 'labels' => []],
            "{$ns}_" . self::TAGS_CREATED_TOTAL => ['type' => 'counter', 'labels' => []],
        ];
    }

    #[\Override]
    public function observeDatabaseQuery(float $seconds): void
    {
        $this->add(self::DB_QUERIES_TOTAL, 1.0);
        $this->observe(self::DB_QUERY_DURATION_SECONDS, $seconds);
    }

    #[\Override]
    public function incrementLogin(string $method, bool $success): void
    {
        $this->add(self::AUTH_LOGINS_TOTAL, 1.0, [$method, $this->result($success)]);
    }

    #[\Override]
    public function incrementRegistration(bool $success): void
    {
        $this->add(self::AUTH_REGISTRATIONS_TOTAL, 1.0, [$this->result($success)]);
    }

    #[\Override]
    public function setUserCount(string $state, int $count): void
    {
        $this->set(self::USERS, (float) $count, [$state]);
    }

    #[\Override]
    public function setActiveUserCount(string $window, int $count): void
    {
        if (! in_array($window, self::ACTIVE_WINDOWS, true)) {
            $this->warn(
                self::USERS_ACTIVE,
                new \InvalidArgumentException(sprintf('unknown active-user window "%s"', $window)),
            );

            return;
        }

        $this->set(self::USERS_ACTIVE, (float) $count, [$window]);
    }

    #[\Override]
    public function incrementChargeCreated(string $chargeType): void
    {
        $this->add(self::CHARGES_CREATED_TOTAL, 1.0, [$this->chargeType($chargeType)]);
    }

    #[\Override]
    public function incrementChargeDeleted(string $chargeType): void
    {
        $this->add(self::CHARGES_DELETED_TOTAL, 1.0, [$this->chargeType($chargeType)]);
    }

    #[\Override]
    public function incrementTagAssignments(int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $this->add(self::TAG_ASSIGNMENTS_TOTAL, (float) $count);
    }

    #[\Override]
    public function incrementWalletCreated(): void
    {
        $this->add(self::WALLETS_CREATED_TOTAL, 1.0);
    }

    #[\Override]
    public function incrementWalletArchived(): void
    {
        $this->add(self::WALLETS_ARCHIVED_TOTAL, 1.0);
    }

    #[\Override]
    public function incrementTagCreated(): void
    {
        $this->add(self::TAGS_CREATED_TOTAL, 1.0);
    }

    /**
     * @param list<non-empty-string> $labels
     */
    private function add(string $name, float $value, array $labels = []): void
    {
        try {
            $this->metrics->add(self::NAMESPACE . '_' . $name, $value, $labels);
        } catch (\Throwable $e) {
            $this->warn($name, $e);
        }
    }

    /**
     * @param list<non-empty-string> $labels
     */
    private function observe(string $name, float $value, array $labels = []): void
    {
        try {
            $this->metrics->observe(self::NAMESPACE . '_' . $name, $value, $labels);
        } catch (\Throwable $e) {
            $this->warn($name, $e);
        }
    }

    /**
     * @param list<non-empty-string> $labels
     */
    private function set(string $name, float $value, array $labels = []): void
    {
        try {
            $this->metrics->set(self::NAMESPACE . '_' . $name, $value, $labels);
        } catch (\Throwable $e) {
            $this->warn($name, $e);
        }
    }

    private function warn(string $name, \Throwable $e): void
    {
        $this->logger->warning('Unable to emit application metric', [
            'metric' => $name,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * @return non-empty-string
     */
    private function result(bool $success): string
    {
        return $success ? self::RESULT_SUCCESS : self::RESULT_FAILURE;
    }

    /**
     * @return non-empty-string
     */
    private function chargeType(string $chargeType): string
    {
        return match ($chargeType) {
            Charge::TYPE_INCOME => 'income',
            Charge::TYPE_EXPENSE => 'expense',
            default => 'unknown',
        };
    }
}
