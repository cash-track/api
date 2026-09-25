<?php

declare(strict_types=1);

namespace App\Service\Idempotency;

use Psr\Log\LoggerInterface;
use Redis;

final class RedisIdempotencyStore implements IdempotencyStoreInterface
{
    const int RECORD_TTL = 86400; // 24 hours — completed responses, replayed to later retries.

    /**
     * In-flight lease. `finally` normally releases the claim early, but doesn't run on a
     * SIGKILL or worker recycle — without this, such a request would strand the key for
     * RECORD_TTL and every retry would 409. A request outliving 60s exposes a duplicate-write
     * window instead, which is the right side to err on.
     */
    const int LEASE_TTL = 60;

    /** Bounds the claim() loop below; unresolved after this many attempts fails open. */
    const int CLAIM_ATTEMPTS = 3;

    public function __construct(
        private readonly Redis $redis,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function claim(string $key, string $fingerprint): IdempotencyClaim
    {
        if (! $this->redis->isConnected()) {
            return IdempotencyClaim::unavailable();
        }

        for ($attempt = 0; $attempt < self::CLAIM_ATTEMPTS; $attempt++) {
            // isConnected() can be stale, so each command is guarded to fail open on a
            // mid-outage drop.
            try {
                $stored = $this->redis->set($key, $this->encode(IdempotencyRecord::inFlight($fingerprint)), [
                    'nx',
                    'ex' => self::LEASE_TTL,
                ]);
            } catch (\Throwable $exception) {
                return $this->unavailable($exception);
            }

            if ($stored !== false) {
                return IdempotencyClaim::claimed();
            }

            try {
                $existing = $this->redis->get($key);
            } catch (\Throwable $exception) {
                return $this->unavailable($exception);
            }

            if (! is_string($existing) || $existing === '') {
                // The key vanished between the failed NX and this GET. Granting a claim never
                // won atomically would let a second racing retry win it too — retry the SET NX.
                continue;
            }

            $record = $this->decode($existing);

            if ($record === null) {
                // Unreadable payload: fail open rather than block the caller on a corrupt key.
                return IdempotencyClaim::unavailable();
            }

            return IdempotencyClaim::conflict($record);
        }

        // Repeatedly lost the SET NX / GET race — fail open rather than grant an unheld claim.
        $this->logger->warning('Idempotency claim retry loop exhausted; failing open', ['key' => $key]);

        return IdempotencyClaim::unavailable();
    }

    #[\Override]
    public function complete(string $key, IdempotencyRecord $record): void
    {
        if (! $this->redis->isConnected()) {
            return;
        }

        try {
            $this->redis->set($key, $this->encode($record), ['ex' => self::RECORD_TTL]);
        } catch (\Throwable $exception) {
            $this->unavailable($exception, 'store the response for');
        }
    }

    #[\Override]
    public function release(string $key): void
    {
        if (! $this->redis->isConnected()) {
            return;
        }

        try {
            $this->redis->del($key);
        } catch (\Throwable $exception) {
            $this->unavailable($exception, 'release');
        }
    }

    /** Fails open, so the gap must be logged. */
    private function unavailable(\Throwable $exception, string $action = 'claim'): IdempotencyClaim
    {
        $this->logger->warning("Idempotency store is unavailable; failing open ({$action})", [
            'exception' => $exception,
        ]);

        return IdempotencyClaim::unavailable();
    }

    private function encode(IdempotencyRecord $record): string
    {
        return (string) json_encode([
            'fingerprint' => $record->fingerprint,
            'status' => $record->status,
            'body' => $record->body === null ? null : base64_encode($record->body),
            'headers' => $record->headers,
        ]);
    }

    private function decode(string $payload): IdempotencyRecord|null
    {
        try {
            /** @var mixed $data */
            $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($data) || ! is_string($data['fingerprint'] ?? null)) {
            return null;
        }

        if (! isset($data['status'])) {
            return IdempotencyRecord::inFlight($data['fingerprint']);
        }

        if (
            ! is_int($data['status'])
            || ! is_string($data['body'] ?? null)
            || ! is_array($data['headers'] ?? null)
        ) {
            return null;
        }

        $body = base64_decode($data['body'], true);

        if ($body === false) {
            return null;
        }

        /** @var array<string, list<string>> $headers */
        $headers = $data['headers'];

        return IdempotencyRecord::completed($data['fingerprint'], $data['status'], $body, $headers);
    }
}
