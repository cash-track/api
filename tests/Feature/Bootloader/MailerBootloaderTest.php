<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\MailerBootloader;
use App\Config\MailConfig;
use App\Service\Mailer\MailerInterface;
use Tests\TestCase;

class MailerBootloaderTest extends TestCase
{
    public function testResolveUnknownTransport(): void
    {
        $config = $this->getMockBuilder(MailConfig::class)->getMock();
        $config->method('getDriver')->willReturn('unknown');

        $bootloader = new MailerBootloader($config);
        $bootloader->boot($this->getContainer());

        $this->expectException(\RuntimeException::class);

        $this->getContainer()->get(MailerInterface::class);
    }
}
