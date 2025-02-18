<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

use RuntimeException;

final class RateLimitReachedException extends RuntimeException
{
    public function __construct(
        protected RateLimitHitInterface $hit,
        string $message = "",
    ) {
        parent::__construct($message);
    }

    public function getHit(): RateLimitHitInterface
    {
        return $this->hit;
    }
}
