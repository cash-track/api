<?php

declare(strict_types=1);

namespace App\Logging;

use Psr\Log\LoggerInterface;
use Spiral\Exceptions\ExceptionReporterInterface;
use Spiral\Sentry\Config\SentryConfig;

/**
 * Replaces Spiral's LoggerReporter, which logs every reported exception at error level,
 * including 404s and 401s. Client exceptions (Sentry's ignore list) go to debug.
 * No 'exception' in context: SentryReporter already sends it.
 */
final class ExceptionLogReporter implements ExceptionReporterInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SentryConfig $config,
    ) {
    }

    #[\Override]
    public function report(\Throwable $exception): void
    {
        $message = sprintf(
            '%s: %s in %s at line %s',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
        );

        if ($this->isClientError($exception)) {
            $this->logger->debug($message);

            return;
        }

        $this->logger->error($message);
    }

    private function isClientError(\Throwable $exception): bool
    {
        foreach ($this->config->getIgnoreExceptions() as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
