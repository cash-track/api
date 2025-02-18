<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

final class GuestRule extends Rule
{
    const string PREFIX = 'guest:';

    protected string $clientIp = '';

    public function __construct(int $limit = 100, int $ttl = 60)
    {
        parent::__construct($limit, $ttl);
    }

    #[\Override]
    public function key(): string
    {
        return static::PREFIX . $this->clientIp;
    }

    public function with(string $clientIp = ''): static
    {
        $this->clientIp = $clientIp;

        return $this;
    }
}
