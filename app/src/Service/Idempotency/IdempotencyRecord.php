<?php

declare(strict_types=1);

namespace App\Service\Idempotency;

/**
 * What is stored under an idempotency key: always a fingerprint of the request that claimed it,
 * and — once the original request finishes — the response to replay for later duplicates.
 */
final readonly class IdempotencyRecord
{
    /**
     * @param array<string, list<string>>|null $headers
     */
    private function __construct(
        public string $fingerprint,
        public int|null $status = null,
        public string|null $body = null,
        public array|null $headers = null,
    ) {
    }

    public static function inFlight(string $fingerprint): self
    {
        return new self($fingerprint);
    }

    /**
     * @param array<string, list<string>> $headers
     */
    public static function completed(string $fingerprint, int $status, string $body, array $headers): self
    {
        return new self($fingerprint, $status, $body, $headers);
    }

    public function isComplete(): bool
    {
        return $this->status !== null;
    }
}
