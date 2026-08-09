<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\GatewayConfig;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The API is publicly routable, so a request that skipped the gateway can forge the headers the
 * gateway owns.
 *
 * IP headers are only rewritten once a secret is configured — without one the gateway is
 * indistinguishable from an attacker, and rewriting would key every request on its address.
 * REMOTE_ADDR replaces them rather than blanking, which would pool everyone into one bucket.
 */
final class InternalHeadersMiddleware implements MiddlewareInterface
{
    private const string HEADER_SECRET = 'X-Gateway-Secret';
    private const string INTERNAL_HEADER_PREFIX = 'X-Internal-';

    public function __construct(
        private readonly GatewayConfig $config,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $secret = $this->config->getSecret();
        $trusted = $secret !== '' && hash_equals($secret, $request->getHeaderLine(self::HEADER_SECRET));

        $request = $this->stripInternalHeaders($request->withoutHeader(self::HEADER_SECRET));

        if ($secret !== '' && ! $trusted) {
            $request = $this->neutralizeIpHeaders($request);
        }

        return $handler->handle($request);
    }

    private function stripInternalHeaders(ServerRequestInterface $request): ServerRequestInterface
    {
        foreach (array_keys($request->getHeaders()) as $name) {
            if (stripos($name, self::INTERNAL_HEADER_PREFIX) === 0) {
                $request = $request->withoutHeader($name);
            }
        }

        return $request;
    }

    private function neutralizeIpHeaders(ServerRequestInterface $request): ServerRequestInterface
    {
        $remoteAddr = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');

        foreach (RateLimitMiddleware::IP_HEADERS as $header) {
            $request = $request->withHeader($header, $remoteAddr);
        }

        return $request;
    }
}
