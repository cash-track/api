<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Middleware\RateLimitMiddleware;
use App\Service\RateLimit\GuestRule;
use App\Service\RateLimit\RateLimitInterface;
use App\Service\RateLimit\RuleFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Tests\Fixtures;
use Tests\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    public const ENV = [
        'REDIS_HOST' => 'localhost',
    ];

    public function setUp(): void
    {
        $this->beforeBooting(static function (ConfiguratorInterface $config): void {
            $config->modify('redis', new Set('prefix', 'CT:testing:'));
        });

        parent::setUp();
    }

    public function tearDown(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $keys = $redis->keys('*');

        if (is_array($keys) && $keys !== []) {
            // del() re-applies OPT_PREFIX, so it must be stripped from keys() results first.
            $redis->del(array_map(
                static fn(string $key): string => substr($key, strlen('CT:testing:')),
                $keys,
            ));
        }

        parent::tearDown();
    }

    public function testHandleGuest(): void
    {
        $ip = long2ip(Fixtures::integer());
        $ip2 = long2ip(Fixtures::integer());
        $ip3 = long2ip(Fixtures::integer());

        $rateLimit = $this->getContainer()->get(RateLimitInterface::class);

        $ruleFactory = $this->getMockBuilder(RuleFactory::class)->getMock();
        $ruleFactory->method('getRule')->with('123', $ip, 'GET', '/v1/wallets')->willReturn($rule = new GuestRule(5));

        $middleware = new RateLimitMiddleware($rateLimit, $ruleFactory);

        $uri = $this->getMockBuilder(UriInterface::class)->getMock();
        $uri->method('getPath')->willReturn('/v1/wallets');

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getHeaderLine')->with('X-Internal-UserId')->willReturn('123');
        $request->method('getHeader')->willReturnMap([
            ['Cf-Original-Connecting-IP', [$ip]],
            ['X-Real-IP', [$ip2]],
            ['X-Forwarded-For', [$ip3]],
        ]);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn(new JsonResponse([], 201));

        for ($i = $rule->limit() - 1; $i !== 0; $i--) {
            $response = $middleware->process($request, $handler);

            $this->assertEquals(201, $response->getStatusCode());
            $this->assertEquals((string) $rule->limit(), $response->getHeaderLine('X-RateLimit-Limit'));
            $this->assertEquals((string) $i, $response->getHeaderLine('X-RateLimit-Remaining'));
        }

        $response = $middleware->process($request, $handler);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals((string) $rule->limit(), $response->getHeaderLine('X-RateLimit-Limit'));
        $this->assertEquals('0', $response->getHeaderLine('X-RateLimit-Remaining'));
        $this->assertEquals($rule->ttl(), $response->getHeaderLine('Retry-After'));
    }

    public function testFetchIpFallsBackToRemoteAddrWhenNoIpHeaderPresent(): void
    {
        $remoteAddr = long2ip(Fixtures::integer());

        $rateLimit = $this->getContainer()->get(RateLimitInterface::class);

        $ruleFactory = $this->getMockBuilder(RuleFactory::class)->getMock();
        $ruleFactory->method('getRule')->with('', $remoteAddr, 'GET', '/v1/wallets')->willReturn(new GuestRule(5));

        $middleware = new RateLimitMiddleware($rateLimit, $ruleFactory);

        $uri = $this->getMockBuilder(UriInterface::class)->getMock();
        $uri->method('getPath')->willReturn('/v1/wallets');

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getHeaderLine')->with('X-Internal-UserId')->willReturn('');
        $request->method('getHeader')->willReturnMap([
            ['Cf-Original-Connecting-IP', []],
            ['X-Real-IP', []],
            ['X-Forwarded-For', []],
        ]);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => $remoteAddr]);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn(new JsonResponse([], 201));

        $response = $middleware->process($request, $handler);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * Regression (D1): a forged X-Internal-UserId sent directly to the API on an unauthenticated
     * 'web'-group route must not upgrade the rate limit from GuestRule (100/60s, IP-keyed) to
     * UserRule (1000/60s). InternalHeadersMiddleware strips the header globally before this
     * group middleware ever sees it.
     */
    public function testWebGroupRateLimitUnaffectedByForgedInternalUserIdHeader(): void
    {
        $response = $this->post('/v1/auth/register/check/nick-name', [
            'nickName' => Fixtures::string(),
        ], [
            'X-Internal-UserId' => '999999',
        ]);

        $response->assertOk();
        $response->assertHasHeader('X-RateLimit-Limit', '100');
    }

    /**
     * P2-4 Part 1 (D1, D2): /auth/login gets its own per-endpoint guest bucket (15/min/IP),
     * enforced end-to-end through real Redis, and that bucket must not share a counter with the
     * default guest bucket used by every other unauthenticated route.
     */
    public function testLoginEndpointGuestRateLimitIsEnforcedAndIndependentFromDefaultBucket(): void
    {
        // Mirrors testHandleGuest above: a fixed-window counter rejects the request that makes
        // the counter reach the limit, so only limit()-1 requests actually succeed.
        for ($i = 14; $i > 0; $i--) {
            $response = $this->post('/v1/auth/login', [
                'email' => Fixtures::email(),
                'password' => Fixtures::string(),
            ]);

            $response->assertStatus(400);
            $response->assertHasHeader('X-RateLimit-Limit', '15');
            $response->assertHasHeader('X-RateLimit-Remaining', (string) $i);
        }

        $response = $this->post('/v1/auth/login', [
            'email' => Fixtures::email(),
            'password' => Fixtures::string(),
        ]);

        $response->assertStatus(429);
        $response->assertHasHeader('X-RateLimit-Limit', '15');
        $response->assertHasHeader('X-RateLimit-Remaining', '0');

        // D1: same IP, different (default-bucket) route — must still have its full 100 budget.
        $nickNameResponse = $this->post('/v1/auth/register/check/nick-name', [
            'nickName' => Fixtures::string(),
        ]);

        $nickNameResponse->assertOk();
        $nickNameResponse->assertHasHeader('X-RateLimit-Limit', '100');
        $nickNameResponse->assertHasHeader('X-RateLimit-Remaining', '99');
    }
}
