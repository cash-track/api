<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class RuleFactory
{
    /**
     * Endpoint-specific guest limits, keyed by "METHOD path" for an exact match — a prefix match
     * would also tighten routes like /auth/register/check/nick-name or /auth/login/passkey, which
     * must stay on the default guest limit.
     *
     * @var array<string, array{slug: string, limit: int, ttl: int}>
     */
    private const array ENDPOINT_RULES = [
        'POST /auth/login' => ['slug' => 'login', 'limit' => 15, 'ttl' => 60],
        'POST /auth/register' => ['slug' => 'register', 'limit' => 3, 'ttl' => 60],
        'POST /auth/password/forgot' => ['slug' => 'password-forgot', 'limit' => 3, 'ttl' => 60],
        'POST /auth/password/reset' => ['slug' => 'password-reset', 'limit' => 5, 'ttl' => 60],
    ];

    public function getRule(string $userId = '', string $clientIp = '', string $method = '', string $path = ''): RuleInterface
    {
        if ($userId === '') {
            $endpoint = self::ENDPOINT_RULES["{$method} {$path}"] ?? null;

            if ($endpoint !== null) {
                return (new GuestEndpointRule($endpoint['slug'], $endpoint['limit'], $endpoint['ttl']))->with($clientIp);
            }

            return (new GuestRule())->with($clientIp);
        }

        return (new UserRule())->with($userId, $clientIp);
    }
}
