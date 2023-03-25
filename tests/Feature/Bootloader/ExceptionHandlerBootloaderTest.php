<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Bootloader\ExceptionHandlerBootloader;
use Tests\TestCase;

class ExceptionHandlerBootloaderTest extends TestCase
{
    public function testAddRenderer(): void
    {
        $bootloader = $this->getContainer()->get(ExceptionHandlerBootloader::class);
        $this->assertEmpty($bootloader->addRenderer());
    }
}
