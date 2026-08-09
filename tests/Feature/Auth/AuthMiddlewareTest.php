<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\AuthMiddleware;
use App\Service\UserService;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Auth\Middleware\AuthMiddleware as Framework;
use Tests\Factories\UserFactory;
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

    public function testProcessLogsAndContinuesWhenActiveAtTrackingFails(): void
    {
        $user = UserFactory::make();
        $user->id = 42;

        $authContext = $this->getMockBuilder(AuthContextInterface::class)->getMock();
        $authContext->method('getActor')->willReturn($user);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getAttribute')->with(Framework::ATTRIBUTE)->willReturn($authContext);
        $request->method('withHeader')->willReturnSelf();
        $request->method('withAttribute')->willReturnSelf();

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn(new JsonResponse([], 200));

        $this->mock(UserService::class, ['store'], function (MockObject $mock) {
            $mock->method('store')->willThrowException(new \RuntimeException('db down'));
        });

        $this->mock(LoggerInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->once())
                 ->method('error')
                 ->with(
                     'Failed to update user active_at',
                     $this->callback(function (array $context) {
                         $this->assertEquals(42, $context['userId']);
                         $this->assertEquals(\RuntimeException::class, $context['error']);
                         $this->assertEquals('db down', $context['message']);

                         return true;
                     }),
                 );
        });

        $middleware = $this->getContainer()->get(AuthMiddleware::class);
        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
