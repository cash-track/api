<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Auth\AuthMiddleware;
use App\Middleware\IdempotencyKeyMiddleware;
use App\Service\Idempotency\IdempotencyClaim;
use App\Service\Idempotency\IdempotencyRecord;
use App\Service\Idempotency\IdempotencyStoreInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\TestCase;

/**
 * Exercises process() in isolation against a mocked store — no HTTP stack, no Redis. The
 * end-to-end wiring and the "two identical requests -> one Charge row" acceptance case live in
 * the Feature-level HTTP test.
 */
class IdempotencyKeyMiddlewareTest extends TestCase
{
    private const string VALID_KEY = '1e6c2a6a-9e2e-4d3a-8f2e-6a2f1b8c9d10';

    private const string PATH = '/v1/tags';

    public function safeMethodDataProvider(): array
    {
        return [
            ['GET'],
            ['HEAD'],
            ['OPTIONS'],
        ];
    }

    /**
     * @dataProvider safeMethodDataProvider
     */
    public function testSafeMethodBypassesEntirelyWithZeroStoreCalls(string $method): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->never())->method('claim');
        $store->expects($this->never())->method('complete');
        $store->expects($this->never())->method('release');

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getMethod')->willReturn($method);

        $handlerResponse = new JsonResponse([], 200);
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
    }

    public function mutatingMethodDataProvider(): array
    {
        return [
            ['POST'],
            ['PUT'],
            ['PATCH'],
            ['DELETE'],
        ];
    }

    /**
     * @dataProvider mutatingMethodDataProvider
     */
    public function testAbsentKeyPassesThroughWithZeroStoreCallsForEveryMutatingMethod(string $method): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->never())->method('claim');

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getMethod')->willReturn($method);
        $request->method('getHeaderLine')->with(IdempotencyKeyMiddleware::HEADER)->willReturn('');

        $handlerResponse = new JsonResponse([], 200);
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
    }

    public function malformedKeyDataProvider(): array
    {
        return [
            'not a uuid at all' => ['not-a-uuid'],
            'uppercase uuid' => ['1E6C2A6A-9E2E-4D3A-8F2E-6A2F1B8C9D10'],
            'wrong version nibble (v3)' => ['1e6c2a6a-9e2e-3d3a-8f2e-6a2f1b8c9d10'],
            'wrong variant nibble' => ['1e6c2a6a-9e2e-4d3a-1f2e-6a2f1b8c9d10'],
            'missing hyphens' => ['1e6c2a6a9e2e4d3a8f2e6a2f1b8c9d10'],
            'braced' => ['{1e6c2a6a-9e2e-4d3a-8f2e-6a2f1b8c9d10}'],
            'too short' => ['1e6c2a6a-9e2e-4d3a-8f2e-6a2f1b8c9d1'],
        ];
    }

    /**
     * @dataProvider malformedKeyDataProvider
     */
    public function testMalformedKeyReturns400ErrorShapedBodyWithoutTouchingTheStore(string $key): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->never())->method('claim');

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getMethod')->willReturn('POST');
        $request->method('getHeaderLine')->with(IdempotencyKeyMiddleware::HEADER)->willReturn($key);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->never())->method('handle');

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame(400, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayNotHasKey('errors', $body);
    }

    public function testFirstRequestClaimsCompletesAndReturnsTheHandlerResponseUnchanged(): void
    {
        $handlerResponse = new JsonResponse(['data' => ['id' => 1]], 201);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())
            ->method('claim')
            ->willReturn(IdempotencyClaim::claimed());
        $store->expects($this->once())
            ->method('complete')
            ->with(
                $this->stringContains('idempotency:42:POST:' . self::PATH . ':' . self::VALID_KEY),
                $this->callback(fn(IdempotencyRecord $record): bool => $record->status === 201 && $record->isComplete()),
            );
        $store->expects($this->never())->method('release');

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{"name":"a"}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
        $this->assertFalse($response->hasHeader(IdempotencyKeyMiddleware::REPLAYED_HEADER));
    }

    /**
     * RateLimitMiddleware runs inside this one, so its X-RateLimit-* values are on $response
     * before the record is stored. A replay never re-runs it, so they must be dropped.
     */
    public function testFirstRequestStripsVolatileHeadersBeforeStoringTheRecordButKeepsOthers(): void
    {
        $handlerResponse = (new JsonResponse(['data' => ['id' => 1]], 201))
            ->withHeader('X-RateLimit-Limit', '60')
            ->withHeader('X-RateLimit-Remaining', '59')
            ->withHeader('X-Foo', 'keep-me');

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $store->expects($this->once())
            ->method('complete')
            ->with(
                $this->anything(),
                $this->callback(static function (IdempotencyRecord $record): bool {
                    $headers = $record->headers ?? [];

                    return ! array_key_exists('X-RateLimit-Limit', $headers)
                        && ! array_key_exists('X-RateLimit-Remaining', $headers)
                        && ($headers['X-Foo'] ?? null) === ['keep-me'];
                }),
            );

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{"name":"a"}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $middleware->process($request, $handler);
    }

    public function testServerErrorResponseReleasesTheClaimInsteadOfCompletingIt(): void
    {
        $handlerResponse = new JsonResponse(['message' => 'boom'], 500);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $store->expects($this->never())->method('complete');
        $store->expects($this->once())
            ->method('release')
            ->with($this->stringContains('idempotency:42:POST:' . self::PATH . ':' . self::VALID_KEY));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testExceptionThrownByHandlerReleasesTheClaimAndPropagates(): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $store->expects($this->never())->method('complete');
        $store->expects($this->once())
            ->method('release')
            ->with($this->stringContains('idempotency:42:POST:' . self::PATH . ':' . self::VALID_KEY));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willThrowException(new \RuntimeException('downstream failure'));

        $middleware = new IdempotencyKeyMiddleware($store);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('downstream failure');

        $middleware->process($request, $handler);
    }

    public function testConflictWithMatchingFingerprintAndCompletedRecordReplaysVerbatim(): void
    {
        $body = '{"name":"a"}';
        $fingerprint = $this->fingerprint('POST', self::PATH, $body);

        $record = IdempotencyRecord::completed($fingerprint, 201, '{"data":{"id":7}}', ['X-Foo' => ['bar']]);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::conflict($record));
        $store->expects($this->never())->method('complete');
        $store->expects($this->never())->method('release');

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, $body);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->never())->method('handle');

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('true', $response->getHeaderLine(IdempotencyKeyMiddleware::REPLAYED_HEADER));
        $this->assertSame('{"data":{"id":7}}', (string) $response->getBody());
        $this->assertSame('bar', $response->getHeaderLine('X-Foo'));
    }

    /**
     * A record stored before VOLATILE_HEADERS existed can still carry stale X-RateLimit-*
     * values, so the replay path must filter too, not just handleFirstRequest().
     */
    public function testReplayDropsVolatileHeadersEvenIfThePersistedRecordStillCarriesThem(): void
    {
        $body = '{"name":"a"}';
        $fingerprint = $this->fingerprint('POST', self::PATH, $body);

        $record = IdempotencyRecord::completed($fingerprint, 200, '{}', [
            'X-RateLimit-Limit' => ['60'],
            'X-RateLimit-Remaining' => ['59'],
            'X-Ct-Trace-Id' => ['old-trace-id'],
            'X-Foo' => ['bar'],
        ]);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::conflict($record));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, $body);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->never())->method('handle');

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertFalse($response->hasHeader('X-RateLimit-Limit'));
        $this->assertFalse($response->hasHeader('X-RateLimit-Remaining'));
        $this->assertFalse($response->hasHeader('X-Ct-Trace-Id'));
        $this->assertSame('bar', $response->getHeaderLine('X-Foo'));
    }

    public function testConflictWithMatchingFingerprintStillInFlightReturns409WithRetryAfter(): void
    {
        $body = '{"name":"a"}';
        $fingerprint = $this->fingerprint('POST', self::PATH, $body);

        $record = IdempotencyRecord::inFlight($fingerprint);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::conflict($record));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, $body);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->never())->method('handle');

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame('1', $response->getHeaderLine('Retry-After'));

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertArrayNotHasKey('errors', $decoded);
    }

    public function testConflictWithMismatchedFingerprintReturns422ErrorShapedBody(): void
    {
        $body = '{"name":"a"}';

        // Deliberately NOT computed from ('POST', self::PATH, $body) — simulates the same key
        // reused with a different request body.
        $record = IdempotencyRecord::completed('some-other-fingerprint', 200, '{}', []);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::conflict($record));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, $body);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->never())->method('handle');

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('message', $decoded);
        // Error-shaped, not ValidationError-shaped, so clients can tell it from a
        // field-validation 422 by the absence of `errors`.
        $this->assertArrayNotHasKey('errors', $decoded);
    }

    /**
     * Skipping complete() for an oversized body must not strand the claim in-flight: $completed
     * stays false, so the finally still releases it. The request is simply not deduplicated.
     */
    public function testOversizedResponseBodySkipsCachingButStillReleasesTheClaim(): void
    {
        $oversizedPayload = str_repeat('a', IdempotencyKeyMiddleware::MAX_CACHEABLE_BODY_BYTES + 1);
        $handlerResponse = new JsonResponse(['data' => $oversizedPayload], 201);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $store->expects($this->never())->method('complete');
        $store->expects($this->once())
            ->method('release')
            ->with($this->stringContains('idempotency:42:POST:' . self::PATH . ':' . self::VALID_KEY));

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
        $this->assertFalse($response->hasHeader(IdempotencyKeyMiddleware::REPLAYED_HEADER));
    }

    public function testStoreUnavailableFailsOpenAndCallsHandlerDirectly(): void
    {
        $handlerResponse = new JsonResponse([], 200);

        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::unavailable());
        $store->expects($this->never())->method('complete');
        $store->expects($this->never())->method('release');

        $request = $this->authenticatedRequest('POST', self::VALID_KEY, '{}');

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handler->expects($this->once())->method('handle')->willReturn($handlerResponse);

        $middleware = new IdempotencyKeyMiddleware($store);

        $response = $middleware->process($request, $handler);

        $this->assertSame($handlerResponse, $response);
    }

    /**
     * An authenticated (X-Internal-UserId: 42) request, so buildKey()/fingerprint() run without
     * falling back to ClientIpResolver.
     */
    private function authenticatedRequest(string $method, string $idempotencyKey, string $body): ServerRequestInterface
    {
        $uri = $this->getMockBuilder(UriInterface::class)->getMock();
        $uri->method('getPath')->willReturn(self::PATH);

        $stream = $this->getMockBuilder(StreamInterface::class)->getMock();
        $stream->method('__toString')->willReturn($body);

        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($uri);
        $request->method('getBody')->willReturn($stream);
        $request->method('getHeaderLine')->willReturnMap([
            [IdempotencyKeyMiddleware::HEADER, $idempotencyKey],
            [AuthMiddleware::HEADER_USER_ID, '42'],
        ]);

        return $request;
    }

    /** Mirrors IdempotencyKeyMiddleware::fingerprint() — there is no public accessor for it. */
    private function fingerprint(string $method, string $path, string $body): string
    {
        return hash('sha256', $method . "\n" . $path . "\n" . $body);
    }
}
