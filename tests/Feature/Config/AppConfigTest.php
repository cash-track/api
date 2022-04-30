<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Config\AppConfig;
use Tests\TestCase;

class AppConfigTest extends TestCase
{
    public function testGetUsersVerifyType(): void
    {
        $config = $this->getContainer()->get(AppConfig::class);
        $this->assertNotEmpty($config->getUrl());
    }
}
