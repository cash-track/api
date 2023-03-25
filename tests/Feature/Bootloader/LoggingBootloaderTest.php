<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\LoggingBootloader;
use Spiral\Config\ConfiguratorInterface;
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

        $this->getContainer()->scope(function (MonologBootloader $monologBootloader, EnvironmentInterface $environment) {
            $bootloader = new LoggingBootloader();
            $bootloader->init($monologBootloader, $environment);
        }, [
            EnvironmentInterface::class => fn () => $env,
            MonologBootloader::class => fn () => $monolog,
        ]);
    }
}
