<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Service\Metrics\AppMetrics;
use App\Service\Metrics\AppMetricsInterface;
use App\Service\Metrics\AppUserMetricsRefresher;
use App\Service\Metrics\DatabaseMetricsLoggerFactory;
use App\Service\Metrics\NullMetrics;
use Cycle\Database\LoggerFactoryInterface;
use Psr\Log\LoggerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Metrics\Metrics;
use Spiral\RoadRunner\Metrics\MetricsInterface;
use Spiral\RoadRunner\Metrics\SuppressExceptionsMetrics;
use Spiral\RoadRunnerBridge\Bootloader\MetricsBootloader as RoadRunnerMetricsBootloader;
use Spiral\Scheduler\Schedule;

/**
 * Wires application business metrics on top of the RoadRunner metrics plugin:
 *  - the collectors are declared statically in `.rr.yaml` (`metrics.collect:`), plugin-side
 *    and once, before any worker boots — so there is no per-worker declare race here;
 *  - routes Cycle's database logger through the metrics-observing factory;
 *  - schedules a periodic refresh of the user gauges (by e-mail state, and DAU/WAU/MAU).
 */
final class MetricsBootloader extends Bootloader
{
    #[\Override]
    public function defineDependencies(): array
    {
        return [
            RoadRunnerMetricsBootloader::class,
        ];
    }

    #[\Override]
    public function defineBindings(): array
    {
        return [
            // Deliberately not a singleton: the wrapper is a cheap, stateless bridge over the
            // shared MetricsInterface, and keeping it non-shared lets feature tests rebind it.
            AppMetricsInterface::class => AppMetrics::class,
        ];
    }

    #[\Override]
    public function defineSingletons(): array
    {
        return [
            // Cycle driver logger -> metrics observer -> framework Monolog channel.
            LoggerFactoryInterface::class => DatabaseMetricsLoggerFactory::class,

            // The real Metrics client talks to RoadRunner over RPC; there is no RoadRunner
            // behind the RPC relay in the test environment, so fall back to a no-op there.
            MetricsInterface::class => static function (
                EnvironmentInterface $env,
                RPCInterface $rpc,
                LoggerInterface $logger,
            ): MetricsInterface {
                if ($env->get('APP_ENV') === 'testing') {
                    return new NullMetrics();
                }

                return new SuppressExceptionsMetrics(new Metrics($rpc), $logger);
            },
        ];
    }

    public function boot(Schedule $schedule): void
    {
        // Collectors are registered by the RR metrics plugin from the static `metrics.collect:`
        // block in `.rr.yaml`; nothing to declare at runtime. The only wiring left here is the
        // periodic refresh of the DB-derived user gauges.
        $this->scheduleUserGaugeRefresh($schedule);
    }

    private function scheduleUserGaugeRefresh(Schedule $schedule): void
    {
        $schedule->call(
            'refresh-app-user-metrics',
            static function (AppUserMetricsRefresher $refresher): void {
                $refresher->refresh();
            },
        )->everyFiveMinutes();
    }
}
