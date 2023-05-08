<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Middleware\RateLimitMiddleware;
use App\Service\RateLimit\GuestRule;
use App\Service\RateLimit\RateLimitInterface;
use App\Service\RateLimit\RuleFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
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
        $redis->del($redis->keys('CT:testing:*'));

        parent::tearDown();
    }

    public function testHandleGuest(): void
    {
        $ip = long2ip(Fixtures::integer());
        $ip2 = long2ip(Fixtures::integer());
        $ip3 = long2ip(Fixtures::integer());

        $rateLimit = $this->getContainer()->get(RateLimitInterface::class);

        $ruleFactory = $this->getMockBuilder(RuleFactory::class)->getMock();
        $ruleFactory->method('getRule')->with('123', $ip)->willReturn($rule = new GuestRule(5));

        $middleware = new RateLimitMiddleware($rateLimit, $ruleFactory);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getHeaderLine')->with('X-Internal-UserId')->willReturn('123');
        $request->method('getHeader')->willReturnMap([
            ['Cf-Original-Connecting-IP', [$ip]],
            ['X-Real-IP', [$ip2]],
            ['X-Forwarded-For', [$ip3]],
        ]);

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
}
