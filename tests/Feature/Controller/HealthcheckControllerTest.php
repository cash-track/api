<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use Tests\DatabaseTransaction;
use Tests\TestCase;

class HealthcheckControllerTest extends TestCase implements DatabaseTransaction
{
    public function testHealthcheckReturnOk(): void
    {
        $response = $this->get('/healthcheck');

        $response->assertOk();
    }
}
