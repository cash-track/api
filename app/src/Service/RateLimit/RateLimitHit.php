<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

class RateLimitHit implements RateLimitHitInterface
{
    public function __construct(
        protected RuleInterface $rule,
        protected int $counter = 0,
        protected int $ttl = 0,
    ) {
    }

    public function isReached(): bool
    {
        return $this->counter >= $this->rule->limit();
    }

    public function getLimit(): int
    {
        return $this->rule->limit();
    }

    public function getRemaining(): int
    {
        if ($this->rule->limit() > $this->counter) {
            return $this->rule->limit() - $this->counter;
        }

        return 0;
    }

    public function getRetryAfter(): int
    {
        return $this->ttl;
    }

    public function getRule(): RuleInterface
    {
        return $this->rule;
    }
}
