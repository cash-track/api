<?php

declare(strict_types=1);

namespace App\Service\Idempotency;

/**
 * Outcome of an attempt to claim an idempotency key: this request claimed it first, another
 * request already holds (or finished with) it, or the store could not be reached at all.
 */
final readonly class IdempotencyClaim
{
    private function __construct(
        public bool $claimed,
        public bool $available,
        public IdempotencyRecord|null $existing,
    ) {
    }

    public static function claimed(): self
    {
        return new self(true, true, null);
    }

    public static function conflict(IdempotencyRecord $record): self
    {
        return new self(false, true, $record);
    }

    public static function unavailable(): self
    {
        return new self(false, false, null);
    }
}
