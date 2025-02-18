<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

final class RateLimitHit implements RateLimitHitInterface
{
    public function __construct(
        protected RuleInterface $rule,
        protected int $counter = 0,
        protected int $ttl = 0,
    ) {
    }

    #[\Override]
    public function isReached(): bool
    {
        return $this->counter >= $this->rule->limit();
    }

    #[\Override]
    public function getLimit(): int
    {
        return $this->rule->limit();
    }

    #[\Override]
    public function getRemaining(): int
    {
        if ($this->rule->limit() > $this->counter) {
            return $this->rule->limit() - $this->counter;
        }

        return 0;
    }

    #[\Override]
    public function getRetryAfter(): int
    {
        return $this->ttl;
    }

    #[\Override]
    public function getRule(): RuleInterface
    {
        return $this->rule;
    }
}
