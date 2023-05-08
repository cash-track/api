<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

interface RateLimitHitInterface
{
    public function isReached(): bool;

    public function getLimit(): int;

    public function getRemaining(): int;

    public function getRetryAfter(): int;

    public function getRule(): RuleInterface;
}
