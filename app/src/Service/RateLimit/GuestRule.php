<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class GuestRule extends Rule
{
    /**
     * @var string
     */
    const PREFIX = 'guest:';

    protected string $clientIp = '';

    public function __construct(int $limit = 100, int $ttl = 60)
    {
        parent::__construct($limit, $ttl);
    }

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
