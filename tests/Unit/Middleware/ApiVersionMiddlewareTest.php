<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\ApiVersionMiddleware;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Boot\EnvironmentInterface;
use Tests\TestCase;

class ApiVersionMiddlewareTest extends TestCase
{
    public function testAddsBothHeadersWhenBothEnvVarsAreSet(): void
    {
        $response = $this->process($this->makeEnvironment('v1.0.0', 'abc123'));

        $this->assertSame('v1.0.0', $response->getHeaderLine('X-Ct-Api-Version'));
        $this->assertSame('abc123', $response->getHeaderLine('X-Ct-Api-Sha'));
    }

    public function testOmitsBothHeadersWhenBothEnvVarsAreUnset(): void
    {
        $response = $this->process($this->makeEnvironment(null, null));

        $this->assertFalse($response->hasHeader('X-Ct-Api-Version'));
        $this->assertFalse($response->hasHeader('X-Ct-Api-Sha'));
    }

    public function testOmitsBothHeadersWhenBothEnvVarsAreEmptyString(): void
    {
        $response = $this->process($this->makeEnvironment('', ''));

        $this->assertFalse($response->hasHeader('X-Ct-Api-Version'));
        $this->assertFalse($response->hasHeader('X-Ct-Api-Sha'));
    }

    public function testAddsOnlyVersionHeaderWhenShaIsEmpty(): void
    {
        $response = $this->process($this->makeEnvironment('v2.0.0', ''));

        $this->assertSame('v2.0.0', $response->getHeaderLine('X-Ct-Api-Version'));
        $this->assertFalse($response->hasHeader('X-Ct-Api-Sha'));
    }

    public function testAddsOnlyShaHeaderWhenVersionIsUnset(): void
    {
        $response = $this->process($this->makeEnvironment(null, 'deadbeef'));

        $this->assertFalse($response->hasHeader('X-Ct-Api-Version'));
        $this->assertSame('deadbeef', $response->getHeaderLine('X-Ct-Api-Sha'));
    }

    public function testHeadersAreStampedOnAnErrorResponseReturnedByTheNextHandler(): void
    {
        $middleware = new ApiVersionMiddleware($this->makeEnvironment('v3.1.4', 'feedface'));

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn(new JsonResponse(['message' => 'Not Found'], 404));

        $response = $middleware->process($request, $handler);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('v3.1.4', $response->getHeaderLine('X-Ct-Api-Version'));
        $this->assertSame('feedface', $response->getHeaderLine('X-Ct-Api-Sha'));
    }

    private function makeEnvironment(?string $gitTag, ?string $gitCommit): EnvironmentInterface
    {
        $environment = $this->getMockBuilder(EnvironmentInterface::class)->getMock();
        $environment->method('get')->willReturnMap([
            ['GIT_TAG', null, $gitTag],
            ['GIT_COMMIT', null, $gitCommit],
        ]);

        return $environment;
    }

    private function process(EnvironmentInterface $environment): ResponseInterface
    {
        $middleware = new ApiVersionMiddleware($environment);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->method('handle')->willReturn(new JsonResponse([], 200));

        return $middleware->process($request, $handler);
    }
}
