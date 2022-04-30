<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Config\CorsConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Spiral\Boot\EnvironmentInterface;
use Tests\Fixtures;
use Tests\TestCase;

class CorsServiceTest extends TestCase
{
    protected function getAllowedOrigin(): ?string
    {
        $origin = $this->getContainer()->get(EnvironmentInterface::class)->get('CORS_ALLOWED_ORIGINS');

        return explode(',', $origin)[0] ?? null;
    }

    public function testPreflightRequest(): void
    {
        $origin = $this->getAllowedOrigin();

        $requestHeaders = [
            'Access-Control-Request-Method' => 'GET',
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Methods');
        $response->assertHasHeader('Access-Control-Allow-Headers');
        $response->assertHasHeader('Access-Control-Max-Age');

        $this->assertArrayContains($origin, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Origin');
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

    public function testNormalRequest(): void
    {
        $origin = $this->getAllowedOrigin();

        $requestHeaders = [
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains($origin, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Origin');
    }

    public function testNormalRequestAllOrigins(): void
    {
        $this->mock(CorsConfig::class, ['getAllowedOrigins', 'getSupportsCredentials'], function (MockObject $mock) {
            $mock->method('getAllowedOrigins')->willReturn(['*']);
            $mock->method('getSupportsCredentials')->willReturn(false);
        });

        $requestHeaders = [
            'Origin' => Fixtures::string(),
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains('*', $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Origin');
    }

    public function testNormalRequestSingleOrigin(): void
    {
        $origin = Fixtures::string();

        $this->mock(CorsConfig::class, ['getAllowedOrigins',], function (MockObject $mock) use ($origin) {
            $mock->method('getAllowedOrigins')->willReturn([$origin]);
        });

        $requestHeaders = [
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains($origin, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Origin');
    }

    public function testNormalRequestAllowedCredentials(): void
    {
        $origin = $this->getAllowedOrigin();

        $this->mock(CorsConfig::class, ['getAllowedOrigins', 'getSupportsCredentials'], function (MockObject $mock) use ($origin) {
            $mock->method('getAllowedOrigins')->willReturn(['*']);
            $mock->method('getSupportsCredentials')->willReturn(true);
        });

        $requestHeaders = [
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Credentials');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains('true', $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Credentials');
    }

    public function testPreflightRequestAllowedAllMethods(): void
    {
        $origin = $this->getAllowedOrigin();

        $this->mock(CorsConfig::class, [
            'getAllowedOrigins', 'getAllowedMethods', 'getAllowedHeaders',
        ], function (MockObject $mock) {
            $mock->method('getAllowedOrigins')->willReturn(['*']);
            $mock->method('getAllowedMethods')->willReturn(['*']);
            $mock->method('getAllowedHeaders')->willReturn(['*']);
        });

        $requestHeaders = [
            'Access-Control-Request-Method' => ['GET'],
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Methods');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains('GET', $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Methods');
    }

    public function testPreflightRequestNotAllowedAllHeaders(): void
    {
        $origin = $this->getAllowedOrigin();
        $header = 'X-Header-Name';

        $this->mock(CorsConfig::class, [
            'getAllowedOrigins', 'getAllowedMethods', 'getAllowedHeaders',
        ], function (MockObject $mock) use ($header) {
            $mock->method('getAllowedOrigins')->willReturn(['*']);
            $mock->method('getAllowedMethods')->willReturn(['*']);
            $mock->method('getAllowedHeaders')->willReturn([$header]);
        });

        $requestHeaders = [
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => $header,
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Headers');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains($header, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Headers');
    }

    public function testNormalRequestExposeHeaders(): void
    {
        $origin = $this->getAllowedOrigin();
        $header = 'X-Header-Name';

        $this->mock(CorsConfig::class, [
            'getAllowedOrigins', 'getExposedHeaders',
        ], function (MockObject $mock) use ($header) {
            $mock->method('getAllowedOrigins')->willReturn(['*']);
            $mock->method('getExposedHeaders')->willReturn([$header]);
        });

        $requestHeaders = [
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Expose-Headers');
        $response->assertHasHeader('Vary');

        $this->assertArrayContains($header, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Expose-Headers');
    }

    public function testPreflightRequestNoOriginHeader(): void
    {
        $origin1 = Fixtures::string();
        $origin2 = Fixtures::string();

        $this->mock(CorsConfig::class, [
            'getAllowedOrigins', 'getAllowedMethods',
        ], function (MockObject $mock) use ($origin1, $origin2) {
            $mock->method('getAllowedOrigins')->willReturn([$origin1, $origin2]);
            $mock->method('getAllowedMethods')->willReturn(['*']);
        });

        $requestHeaders = [
            'Access-Control-Request-Method' => ['GET'],
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
        $response->assertHeaderMissing('Access-Control-Allow-Methods');
    }

    public function testPreflightRequestAllowedOriginPattern(): void
    {
        $origin1 = Fixtures::domain();
        $origin2 = Fixtures::domain();
        $origin3 = Fixtures::domain();
        $origin = $origin3 . '/' . Fixtures::string();

        $this->mock(CorsConfig::class, [
            'getAllowedOrigins', 'getAllowedOriginsPatterns', 'getAllowedMethods', 'getAllowedHeaders',
        ], function (MockObject $mock) use ($origin1, $origin2, $origin3) {
            $mock->method('getAllowedOrigins')->willReturn([$origin1, $origin2]);
            $mock->method('getAllowedOriginsPatterns')->willReturn(["{$origin3}/*"]);
            $mock->method('getAllowedMethods')->willReturn(['*']);
            $mock->method('getAllowedHeaders')->willReturn(['X-Header-Name']);
        });

        $requestHeaders = [
            'Access-Control-Request-Method' => ['GET'],
            'Origin' => $origin,
        ];

        $response = $this->fakeHttp()->optionsJson('/currencies', $requestHeaders);

        $response->assertHasHeader('Access-Control-Allow-Origin');
        $response->assertHasHeader('Access-Control-Allow-Methods');

        $this->assertArrayContains($origin, $response->getOriginalResponse()->getHeaders(), 'Access-Control-Allow-Origin');
    }
}
