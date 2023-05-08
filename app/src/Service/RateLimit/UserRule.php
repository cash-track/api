<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class UserRule extends Rule
{
    /**
     * @var string
     */
    const PREFIX = 'user:';

    protected string $userId = '';

    protected string $clientIp = '';

    public function __construct(int $limit = 1000, int $ttl = 60)
    {
        parent::__construct($limit, $ttl);
    }

    public function key(): string
    {
        $key = static::PREFIX . $this->userId;

        if ($this->clientIp !== '') {
            $key .= "-{$this->clientIp}";
        }

        return $key;
    }

    public function with(string $userId = '', string $clientIp = ''): static
    {
        $this->userId = $userId;
        $this->clientIp = $clientIp;

        return $this;
    }
}
