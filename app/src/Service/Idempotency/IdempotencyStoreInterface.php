<?php

declare(strict_types=1);

namespace App\Service\Idempotency;

interface IdempotencyStoreInterface
{
    /**
     * Atomically claims $key for $fingerprint if nothing is stored under it yet.
     */
    public function claim(string $key, string $fingerprint): IdempotencyClaim;

    /**
     * Overwrites the in-flight marker with the finished response, refreshing the TTL.
     */
    public function complete(string $key, IdempotencyRecord $record): void;

    /**
     * Releases a claim: used when the original request failed (5xx) or threw, so a genuine
     * retry is free to run again instead of being pinned to that failure for the full TTL.
     */
    public function release(string $key): void;
}
