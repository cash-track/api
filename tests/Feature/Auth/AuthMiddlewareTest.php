<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\AuthMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Auth\Middleware\AuthMiddleware as Framework;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    public function testProcessNoAuthContext(): void
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getAttribute')->with(Framework::ATTRIBUTE)->willReturn(null);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $middleware = $this->getContainer()->get(AuthMiddleware::class);
        $response = $middleware->process($request, $handler);

        $this->assertEquals(401, $response->getStatusCode());
    }
}
