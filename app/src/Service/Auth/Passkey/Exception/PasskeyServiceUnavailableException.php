<?php

declare(strict_types=1);

namespace App\Service\Auth\Passkey\Exception;

/**
 * Thrown when passkey challenge storage (Redis) is unreachable. Passkey ceremonies have no
 * degraded mode, so this is a distinct, retryable failure rather than a generic exception.
 */
final class PasskeyServiceUnavailableException extends \RuntimeException
{
}
