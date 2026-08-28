<?php

declare(strict_types=1);

namespace App\Service\Metrics;

/**
 * Application-level business metrics. Thin, side-effect-only contract on top of the
 * RoadRunner metrics plugin; every method is a no-op when metrics emission fails so a
 * telemetry outage can never break a user request.
 */
interface AppMetricsInterface
{
    /**
     * One executed SQL statement: bumps the query counter and observes its duration.
     */
    public function observeDatabaseQuery(float $seconds): void;

    /**
     * @param non-empty-string $method one of password|google|passkey
     */
    public function incrementLogin(string $method, bool $success): void;

    public function incrementRegistration(bool $success): void;

    /**
     * @param non-empty-string $state one of verified|unverified
     */
    public function setUserCount(string $state, int $count): void;

    /**
     * Registered users active within a rolling window.
     *
     * @param non-empty-string $window one of daily|weekly|monthly
     */
    public function setActiveUserCount(string $window, int $count): void;

    /**
     * @param string $chargeType raw Charge::TYPE_* value ('+' income, '-' expense)
     */
    public function incrementChargeCreated(string $chargeType): void;

    /**
     * @param string $chargeType raw Charge::TYPE_* value ('+' income, '-' expense)
     */
    public function incrementChargeDeleted(string $chargeType): void;

    public function incrementTagAssignments(int $count): void;

    public function incrementWalletCreated(): void;

    public function incrementWalletArchived(): void;

    public function incrementTagCreated(): void;
}
