<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use App\Exception\AuthenticationRequiredException;
use App\Exception\UnconfirmedProfileException;
use App\Sentry\BeforeSend;
use Sentry\Options;
use Spiral\Http\Exception\ClientException\BadRequestException;
use Spiral\Http\Exception\ClientException\ForbiddenException;
use Spiral\Http\Exception\ClientException\NotFoundException;
use Spiral\Http\Exception\ClientException\ServerErrorException;
use Spiral\Http\Exception\ClientException\UnauthorizedException;
use Spiral\Router\Exception\RouteNotFoundException;
use Spiral\Router\Exception\UndefinedRouteException;
use Tests\TestCase;

class SentryConfigTest extends TestCase
{
    public function testTracingDisabledAndBeforeSendWired(): void
    {
        $options = $this->getContainer()->get(Options::class);

        $this->assertNull($options->getTracesSampleRate());
        $this->assertInstanceOf(BeforeSend::class, $options->getBeforeSendCallback());
    }

    /**
     * @dataProvider exceptionsProvider
     */
    public function testIgnoreList(\Throwable $e, bool $ignored): void
    {
        $options = $this->getContainer()->get(Options::class);

        $matches = array_filter($options->getIgnoreExceptions(), static fn (string $class): bool => $e instanceof $class);

        $this->assertSame($ignored, $matches !== []);
    }

    public function exceptionsProvider(): array
    {
        return [
            'route not found' => [new RouteNotFoundException(new \Nyholm\Psr7\Uri('/nope')), true],
            'undefined route' => [new UndefinedRouteException(), true],
            'bad request' => [new BadRequestException(), true],
            'unauthorized' => [new UnauthorizedException(), true],
            'forbidden' => [new ForbiddenException(), true],
            'not found' => [new NotFoundException(), true],
            'auth required' => [new AuthenticationRequiredException(), true],
            'unconfirmed profile' => [new UnconfirmedProfileException(), true],
            'server error stays reported' => [new ServerErrorException(), false],
            'generic runtime' => [new \RuntimeException('boom'), false],
        ];
    }
}
