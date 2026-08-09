<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

/**
 * Guest rule for a single tightened endpoint (e.g. login, register). Keyed by $slug so it
 * doesn't share a counter with the default GuestRule bucket used by general guest traffic.
 */
final class GuestEndpointRule extends GuestRule
{
    public function __construct(
        private readonly string $slug,
        int $limit,
        int $ttl = 60,
    ) {
        parent::__construct($limit, $ttl);
    }

    #[\Override]
    public function key(): string
    {
        return static::PREFIX . $this->slug . ':' . $this->clientIp;
    }
}
