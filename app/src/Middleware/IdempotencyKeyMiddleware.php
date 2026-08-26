<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthMiddleware;
use App\Http\ClientIpResolver;
use App\Service\Idempotency\IdempotencyRecord;
use App\Service\Idempotency\IdempotencyStoreInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Router\Router;
use Spiral\Translator\Traits\TranslatorTrait;

/**
 * Deduplicates mutating requests carrying an `Idempotency-Key` header, so a retry fired by
 * Traefik/gateway/fasthttp doesn't re-execute the controller and double-apply a charge.
 *
 * The header is optional; its absence just means no deduplication. Enforcement comes later.
 */
final class IdempotencyKeyMiddleware implements MiddlewareInterface
{
    use TranslatorTrait;

    const string HEADER = 'Idempotency-Key';
    const string REPLAYED_HEADER = 'Idempotency-Replayed';

    /** RFC 4122 UUIDv4, canonical lowercase 8-4-4-4-12 form only — no braces, no uppercase. */
    const string UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    const array MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Routes whose 200 body carries live `accessToken` / `refreshToken` values (every caller of
     * Auth\Controller::responseTokensWithUser()). Caching one for RECORD_TTL would leave live
     * credentials in Redis for 24h, and replaying /auth/refresh is wrong anyway since rotation
     * is its whole point. Nothing is lost: a duplicate login has no side effect to prevent.
     *
     * Matched on Router::ROUTE_NAME, not the path — the path here already carries the group
     * prefix (`/v1/auth/login`), so un-prefixed path strings would silently never match.
     */
    const array CREDENTIAL_ISSUING_ROUTES = [
        'auth.login',
        'auth.login.passkey',
        'auth.provider.google',
        'auth.refresh',
        'auth.register',
    ];

    /** Small on purpose: the original request is expected to finish in well under a second. */
    const int CONFLICT_RETRY_AFTER = 1;

    /** Generous cap, not a tuned limit — an oversized response is simply not cached. */
    const int MAX_CACHEABLE_BODY_BYTES = 256 * 1024;

    /**
     * Headers correct only for the response that produced them, so never replayed:
     *  - `x-ratelimit-*`: a replay skips RateLimitMiddleware, so its counters would be stale.
     *  - `date` / `set-cookie`: request-scoped. Unset today, excluded so a future change can't
     *    pin one for the record's 24h TTL.
     *  - `x-ct-trace-id`, `x-ct-api-version`, `x-ct-api-sha`: stamped outside this middleware
     *    and re-stamped on every replay; listed as a guard against a future reordering.
     *
     * Lowercase — matched case-insensitively per RFC 7230.
     */
    const array VOLATILE_HEADERS = [
        'date',
        'set-cookie',
        'x-ratelimit-limit',
        'x-ratelimit-remaining',
        'x-ct-trace-id',
        'x-ct-api-version',
        'x-ct-api-sha',
    ];

    public function __construct(
        private readonly IdempotencyStoreInterface $store,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (! in_array($request->getMethod(), self::MUTATING_METHODS, true)) {
            return $handler->handle($request);
        }

        $routeName = $request->getAttribute(Router::ROUTE_NAME);

        if (is_string($routeName) && in_array($routeName, self::CREDENTIAL_ISSUING_ROUTES, true)) {
            // No claim, no cache, no replay — falls through like a request without a key, so
            // RateLimitMiddleware still runs normally.
            return $handler->handle($request);
        }

        $key = $request->getHeaderLine(self::HEADER);

        if ($key === '') {
            return $handler->handle($request);
        }

        if (! $this->isValidKey($key)) {
            return $this->badRequest();
        }

        $redisKey = $this->buildKey($request, $key);
        $fingerprint = $this->fingerprint($request);

        $claim = $this->store->claim($redisKey, $fingerprint);

        if (! $claim->available) {
            // Redis is down: process as if no key was ever supplied.
            return $handler->handle($request);
        }

        if (! $claim->claimed) {
            /** @var IdempotencyRecord $existing */
            $existing = $claim->existing;

            return $this->handleExisting($existing, $fingerprint);
        }

        return $this->handleFirstRequest($request, $handler, $redisKey, $fingerprint);
    }

    private function handleFirstRequest(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        string $redisKey,
        string $fingerprint,
    ): ResponseInterface {
        $completed = false;

        try {
            $response = $handler->handle($request);

            // A 5xx is the transient failure a client is entitled to retry; pinning it for 24h
            // would be worse than the duplicate it prevents.
            if ($response->getStatusCode() >= 500) {
                return $response;
            }

            $body = (string) $response->getBody();

            if (strlen($body) > self::MAX_CACHEABLE_BODY_BYTES) {
                return $response;
            }

            $this->store->complete($redisKey, IdempotencyRecord::completed(
                $fingerprint,
                $response->getStatusCode(),
                $body,
                $this->normalizeHeaders($response->getHeaders()),
            ));
            $completed = true;

            return $response;
        } finally {
            if (! $completed) {
                $this->store->release($redisKey);
            }
        }
    }

    private function handleExisting(IdempotencyRecord $record, string $fingerprint): ResponseInterface
    {
        if ($record->fingerprint !== $fingerprint) {
            return $this->unprocessable();
        }

        if (! $record->isComplete()) {
            return $this->inFlight();
        }

        return $this->replay($record);
    }

    private function replay(IdempotencyRecord $record): ResponseInterface
    {
        // Re-filtered, not trusted: a record written before VOLATILE_HEADERS existed could
        // still carry stale X-RateLimit-* values.
        $headers = $this->normalizeHeaders($record->headers ?? []);

        $response = new Response('php://memory', $record->status ?? 200, $headers);
        $response->getBody()->write($record->body ?? '');
        $response->getBody()->rewind();

        return $response->withHeader(self::REPLAYED_HEADER, 'true');
    }

    /**
     * Normalises a PSR-7 header array into the shape IdempotencyRecord and Response require,
     * dropping VOLATILE_HEADERS.
     *
     * @param array<array-key, array<array-key, mixed>> $headers
     * @return array<non-empty-string, list<string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $values) {
            $name = (string) $name;

            if ($name === '' || in_array(strtolower($name), self::VOLATILE_HEADERS, true)) {
                continue;
            }

            $normalized[$name] = array_values(array_map(static fn(mixed $value): string => (string) $value, $values));
        }

        return $normalized;
    }

    private function isValidKey(string $key): bool
    {
        return preg_match(self::UUID_V4_PATTERN, $key) === 1;
    }

    private function buildKey(ServerRequestInterface $request, string $key): string
    {
        $userId = $request->getHeaderLine(AuthMiddleware::HEADER_USER_ID);
        $scope = $userId !== '' ? $userId : ClientIpResolver::resolve($request);

        return sprintf(
            'idempotency:%s:%s:%s:%s',
            $scope,
            $request->getMethod(),
            $request->getUri()->getPath(),
            $key,
        );
    }

    private function fingerprint(ServerRequestInterface $request): string
    {
        return hash(
            'sha256',
            $request->getMethod() . "\n" . $request->getUri()->getPath() . "\n" . (string) $request->getBody(),
        );
    }

    private function badRequest(): ResponseInterface
    {
        return new JsonResponse([
            'message' => $this->say('error_idempotency_key_invalid'),
        ], 400);
    }

    private function inFlight(): ResponseInterface
    {
        return new JsonResponse([
            'message' => $this->say('error_idempotency_key_in_flight'),
        ], 409)->withHeader('Retry-After', (string) self::CONFLICT_RETRY_AFTER);
    }

    private function unprocessable(): ResponseInterface
    {
        return new JsonResponse([
            'message' => $this->say('error_idempotency_key_mismatch'),
        ], 422);
    }
}
