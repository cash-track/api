<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use Spiral\Boot\EnvironmentInterface;
use Tests\Fixtures;
use Tests\TestCase;

class CorsServiceTest extends TestCase
{
    public function testPreflightRequest(): void
    {
        $origin = $this->getContainer()->get(EnvironmentInterface::class)->get('CORS_ALLOWED_ORIGINS');
        $origin = explode(',', $origin)[0] ?? null;

        $requestHeaders = [
            'Access-Control-Request-Method' => 'GET',
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Methods');
        $response->assertHasHeader('Access-Control-Allow-Headers');
        $response->assertHasHeader('Access-Control-Max-Age');
    }

    public function testPreflightRequestRejected(): void
    {
        $origin = Fixtures::string();

        $requestHeaders = [
            'Access-Control-Request-Method' => 'GET',
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
        $response->assertHeaderMissing('Access-Control-Allow-Methods');
        $response->assertHeaderMissing('Access-Control-Allow-Headers');
        $response->assertHeaderMissing('Access-Control-Max-Age');
    }
}
