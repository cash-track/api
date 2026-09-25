<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Adds a readable 'error' string next to context['exception']: the roadrunner handler
 * JSON-encodes a Throwable as {}. The object stays for the Sentry handler.
 */
final class ExceptionContextProcessor implements ProcessorInterface
{
    #[\Override]
    public function __invoke(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        if (!$exception instanceof \Throwable || isset($record->context['error'])) {
            return $record;
        }

        return $record->with(context: $record->context + [
            'error' => sprintf(
                '%s: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ),
        ]);
    }
}
