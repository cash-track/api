<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class Rule implements RuleInterface
{
    const string PREFIX = '';

    public function __construct(
        protected int $limit = 0,
        protected int $ttl = 60,
    ) {
    }

    public function key(): string
    {
        return static::PREFIX;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function withLimit(int $limit): static
    {
        $self = clone $this;
        $self->limit = $limit;
        return $self;
    }

    public function withTtl(int $ttl): static
    {
        $self = clone $this;
        $self->ttl = $ttl;
        return $self;
    }
}
