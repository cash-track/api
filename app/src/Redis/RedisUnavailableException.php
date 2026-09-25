<?php

declare(strict_types=1);

namespace App\Redis;

/**
 * Raised by ReconnectingRedis when a connect attempt fails. BeforeSend groups these into one Sentry issue.
 */
final class RedisUnavailableException extends \RedisException
{
}
