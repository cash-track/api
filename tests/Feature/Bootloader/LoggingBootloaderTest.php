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
        $env = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $env->expects($this->once())->method('get')->with('DEBUG')->willReturn(true);

        $config = $this->getMockBuilder(ConfiguratorInterface::class)->getMock();

        $monolog = new MonologBootloader($config);

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
