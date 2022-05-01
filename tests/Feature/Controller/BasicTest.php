<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use Tests\TestCase;

class BasicTest extends TestCase
{
    public function testDefaultActionWorks(): void
    {
        $response = $this->get('/');

        $this->assertEquals(404, $response->getStatusCode());
    }
}
