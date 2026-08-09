<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Middleware\InternalHeadersMiddleware;
use App\Middleware\RateLimitMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Testing\Attribute\Env;
use Tests\TestCase;

class InternalHeadersMiddlewareTest extends TestCase
{
    private const array FORGED_HEADERS = [
        'X-Gateway-Secret' => 'attacker-supplied',
        'X-Internal-UserId' => '999',
        'X-Internal-UserLocale' => 'fr',
        'Cf-Original-Connecting-IP' => '198.51.100.1',
        'X-Real-IP' => '198.51.100.2',
        'X-Forwarded-For' => '198.51.100.3',
    ];

    private function capture(ServerRequestInterface $request): ServerRequestInterface
    {
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $captured = null;
        $handler->method('handle')->willReturnCallback(
            static function (ServerRequestInterface $request) use (&$captured): ResponseInterface {
                $captured = $request;

                return new JsonResponse([], 200);
            },
        );

        $middleware = $this->getContainer()->get(InternalHeadersMiddleware::class);
        $middleware->process($request, $handler);

        $this->assertInstanceOf(ServerRequestInterface::class, $captured);

        return $captured;
    }

    public function testSecretUnsetStripsInternalHeadersAndLeavesIpHeadersAlone(): void
    {
        $request = new ServerRequest('GET', '/', self::FORGED_HEADERS, null, '1.1', ['REMOTE_ADDR' => '203.0.113.9']);

        $downstream = $this->capture($request);

        $this->assertSame('', $downstream->getHeaderLine('X-Gateway-Secret'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserId'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserLocale'));

        // no secret configured: interim rollout state, IP headers pass through untouched
        $this->assertSame('198.51.100.1', $downstream->getHeaderLine('Cf-Original-Connecting-IP'));
        $this->assertSame('198.51.100.2', $downstream->getHeaderLine('X-Real-IP'));
        $this->assertSame('198.51.100.3', $downstream->getHeaderLine('X-Forwarded-For'));
    }

    #[Env('GATEWAY_SECRET', 'top-secret')]
    public function testSecretConfiguredAndMissingReplacesIpHeadersWithRemoteAddr(): void
    {
        $headers = self::FORGED_HEADERS;
        unset($headers['X-Gateway-Secret']);

        $request = new ServerRequest('GET', '/', $headers, null, '1.1', ['REMOTE_ADDR' => '203.0.113.9']);

        $downstream = $this->capture($request);

        $this->assertSame('', $downstream->getHeaderLine('X-Gateway-Secret'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserId'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserLocale'));

        foreach (RateLimitMiddleware::IP_HEADERS as $header) {
            $this->assertSame('203.0.113.9', $downstream->getHeaderLine($header), "Header {$header}");
        }
    }

    #[Env('GATEWAY_SECRET', 'top-secret')]
    public function testSecretConfiguredAndWrongReplacesIpHeadersWithRemoteAddr(): void
    {
        $headers = self::FORGED_HEADERS;
        $headers['X-Gateway-Secret'] = 'wrong-secret';

        $request = new ServerRequest('GET', '/', $headers, null, '1.1', ['REMOTE_ADDR' => '203.0.113.9']);

        $downstream = $this->capture($request);

        $this->assertSame('', $downstream->getHeaderLine('X-Gateway-Secret'));

        foreach (RateLimitMiddleware::IP_HEADERS as $header) {
            $this->assertSame('203.0.113.9', $downstream->getHeaderLine($header), "Header {$header}");
        }
    }

    #[Env('GATEWAY_SECRET', 'top-secret')]
    public function testSecretConfiguredAndCorrectLeavesIpHeadersUntouched(): void
    {
        $headers = self::FORGED_HEADERS;
        $headers['X-Gateway-Secret'] = 'top-secret';

        $request = new ServerRequest('GET', '/', $headers, null, '1.1', ['REMOTE_ADDR' => '203.0.113.9']);

        $downstream = $this->capture($request);

        // the secret header is stripped even when it matched — it must never reach a controller/filter
        $this->assertSame('', $downstream->getHeaderLine('X-Gateway-Secret'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserId'));
        $this->assertSame('', $downstream->getHeaderLine('X-Internal-UserLocale'));

        $this->assertSame('198.51.100.1', $downstream->getHeaderLine('Cf-Original-Connecting-IP'));
        $this->assertSame('198.51.100.2', $downstream->getHeaderLine('X-Real-IP'));
        $this->assertSame('198.51.100.3', $downstream->getHeaderLine('X-Forwarded-For'));
    }
}
