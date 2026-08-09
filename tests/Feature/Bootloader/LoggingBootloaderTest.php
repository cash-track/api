<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\LoggingBootloader;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Core\Container;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiral\Boot\EnvironmentInterface;
use Tests\TestCase;

class LoggingBootloaderTest extends TestCase
{
    public function testDebugConfig(): void
    {
        // willReturnMap can't express "return whatever default was passed", and its rows are
        // matched by == against literal arguments, so $this->anything() as a row value never
        // matches — a callback is the only reliable way to stub both DEBUG and MONOLOG_FORMAT.
        $env = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $env->expects($this->exactly(5))->method('get')->willReturnCallback(
            static fn (string $name, mixed $default = null): mixed => match ($name) {
                'DEBUG' => true,
                default => $default,
            },
        );

        $config = $this->getMockBuilder(ConfiguratorInterface::class)->getMock();

        $monolog = new MonologBootloader($config, $env);

        $this->getContainer()->runScope([
            EnvironmentInterface::class => fn () => $env,
            MonologBootloader::class => fn () => $monolog,
        ], function (Container $container) {
            $bootloader = new LoggingBootloader();
            $bootloader->init(
                $container->get(MonologBootloader::class),
                $container->get(EnvironmentInterface::class)
            );
        });
    }
}
