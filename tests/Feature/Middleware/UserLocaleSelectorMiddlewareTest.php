<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Auth\AuthMiddleware;
use App\Middleware\UserLocaleSelectorMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Translator\Translator;
use Tests\TestCase;

class UserLocaleSelectorMiddlewareTest extends TestCase
{
    public function testProcessInvalidLocale(): void
    {
        $locale = 'uk';

        /** @var UserLocaleSelectorMiddleware $middleware */
        $middleware = $this->getContainer()->get(UserLocaleSelectorMiddleware::class);

        /** @var \Spiral\Translator\Translator $translator */
        $translator = $this->getContainer()->get(Translator::class);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $request->method('getAttribute')->with(AuthMiddleware::USER_LOCALE)->willReturn($locale);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $handler->method('handle');

        $middleware->process($request, $handler);

        $this->assertEquals($locale, $translator->getLocale());
    }
}
