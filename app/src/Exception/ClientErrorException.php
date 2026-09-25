<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Request cannot be fulfilled because of client input or state (invalid code, expired token,
 * throttled). The message is translated and safe to return. Not logged as an error.
 */
class ClientErrorException extends \RuntimeException
{
}
