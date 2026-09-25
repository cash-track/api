<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Forwards ErrorHandlerMiddleware's 5xx log lines to the default logger, so they reach
 * stdout/Loki in prod. 4xx (bot 404 scans, expired-token 401s, ...) stay out of stdout.
 * Warning, not error: ExceptionLogReporter already logs the 5xx at error; this line only
 * adds the request path. Never stops the chain: http.log must still get every error.
 */
final class ForwardServerErrorsHandler extends AbstractHandler
{
    private const string PATTERN = '/caused the error 5\d\d /';

    /**
     * @param \Closure(): LoggerInterface $logger Resolved lazily, only when a 5xx actually
     *     fires — never during bootload, when Monolog's own config may still be mutable.
     */
    public function __construct(private readonly \Closure $logger)
    {
        parent::__construct();
    }

    #[\Override]
    public function handle(LogRecord $record): bool
    {
        if (preg_match(self::PATTERN, $record->message) === 1) {
            ($this->logger)()->warning($record->message, $record->context);
        }

        return false;
    }
}
