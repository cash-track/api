<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\LoggingBootloader;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Core\ContainerScope;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Tests\TestCase;

class LoggingBootloaderTest extends TestCase
{
    public function testConfigureDebug(): void
    {
        $env = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $env->expects($this->once())->method('get')->with('DEBUG')->willReturn(true);

        ContainerScope::runScope($this->getContainer(), function () use ($env) {
            $bootloader = new LoggingBootloader();
            $bootloader->boot($this->getContainer()->get(MonologBootloader::class), $env);
        });
    }
}
