<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the caller's IP from the header precedence the gateway is trusted to set, falling
 * back to REMOTE_ADDR. Shared by RateLimitMiddleware and IdempotencyKeyMiddleware so both key
 * on the same address for the same request.
 */
final class ClientIpResolver
{
    const array IP_HEADERS = [
        'Cf-Original-Connecting-IP',
        'X-Real-IP',
        'X-Forwarded-For',
    ];

    public static function resolve(ServerRequestInterface $request): string
    {
        foreach (self::IP_HEADERS as $header) {
            $ip = $request->getHeader($header)[0] ?? '';

            if ($ip !== '') {
                return $ip;
            }
        }

        return (string) ($request->getServerParams()['REMOTE_ADDR'] ?? '');
    }
}
