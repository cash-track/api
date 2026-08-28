<?php

declare(strict_types=1);

namespace Tests\Unit\Bootloader;

use App\Bootloader\MetricsBootloader;
use App\Service\Metrics\NullMetrics;
use Psr\Log\LoggerInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Metrics\MetricsInterface;
use Spiral\RoadRunner\Metrics\SuppressExceptionsMetrics;
use Tests\TestCase;

final class MetricsBootloaderTest extends TestCase
{
    public function testMetricsFactoryFallsBackToNullMetricsInTestEnvironment(): void
    {
        $metrics = ($this->metricsFactory())(
            $this->env('testing'),
            $this->rpc(),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(NullMetrics::class, $metrics);
    }

    public function testMetricsFactoryUsesSuppressingRoadRunnerClientOutsideTests(): void
    {
        $metrics = ($this->metricsFactory())(
            $this->env('prod'),
            $this->rpc(),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(SuppressExceptionsMetrics::class, $metrics);
    }

    private function metricsFactory(): \Closure
    {
        $factory = (new MetricsBootloader())->defineSingletons()[MetricsInterface::class];

        $this->assertInstanceOf(\Closure::class, $factory);

        return $factory;
    }

    private function env(string $appEnv): EnvironmentInterface
    {
        $env = $this->createMock(EnvironmentInterface::class);
        $env->method('get')->willReturnCallback(
            static fn (string $name, mixed $default = null): mixed => $name === 'APP_ENV' ? $appEnv : $default,
        );

        return $env;
    }

    private function rpc(): RPCInterface
    {
        $rpc = $this->createMock(RPCInterface::class);
        $rpc->method('withServicePrefix')->willReturnSelf();
        $rpc->method('withCodec')->willReturnSelf();

        return $rpc;
    }
}
