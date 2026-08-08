<?php

declare(strict_types=1);

namespace App\Service\Auth;

/** Thrown by LoginBackoffService when the per-email failed-login backoff is still in effect. */
final class LoginThrottledException extends \RuntimeException
{
    public function __construct(
        private readonly int $retryAfter,
    ) {
        parent::__construct('Too many failed login attempts for this email.');
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
