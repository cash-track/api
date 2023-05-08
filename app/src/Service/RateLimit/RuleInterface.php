<?php

declare(strict_types=1);

namespace App\Service\RateLimit;

interface RuleInterface
{
    public function key(): string;

    public function limit(): int;

    public function ttl(): int;

    public function withLimit(int $limit): static;

    public function withTtl(int $ttl): static;
}
