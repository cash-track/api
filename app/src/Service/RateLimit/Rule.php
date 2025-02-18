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

    #[\Override]
    public function key(): string
    {
        return static::PREFIX;
    }

    #[\Override]
    public function limit(): int
    {
        return $this->limit;
    }

    #[\Override]
    public function ttl(): int
    {
        return $this->ttl;
    }

    #[\Override]
    public function withLimit(int $limit): static
    {
        $self = clone $this;
        $self->limit = $limit;
        return $self;
    }

    #[\Override]
    public function withTtl(int $ttl): static
    {
        $self = clone $this;
        $self->ttl = $ttl;
        return $self;
    }
}
