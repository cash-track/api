<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

interface RateLimitInterface
{
    public function hit(RuleInterface $rule): RateLimitHitInterface;
}
