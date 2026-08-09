<?php

declare(strict_types=1);

use App\Bootloader\LoggingBootloader;
use App\Logging\TraceIdProcessor;
use Cycle\Database\Driver\MySQL\MySQLDriver;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Spiral\Http\Middleware\ErrorHandlerMiddleware;

// Spiral's 'log.rotate' handler alias applies Spiral\Monolog\Bootloader\MonologBootloader::DEFAULT_FORMAT,
// which has no %extra% placeholder — trace_id (written to extra by TraceIdProcessor) would be silently
// dropped from app.log. Build the handler directly with an explicit format that includes it, shared with
// LoggingBootloader's own rotating file handlers via LoggingBootloader::LOG_FORMAT.
$appLogHandler = new RotatingFileHandler(
    filename: directory('runtime').'logs/app.log',
    level: Logger::DEBUG,
    useLocking: true,
);
$appLogHandler->setFormatter(new LineFormatter(LoggingBootloader::LOG_FORMAT));

return [

    /**
     * Monolog supports the logging levels described by RFC 5424.
     *
     * @see https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md#log-levels
     */
    'globalLevel' => Logger::toMonologLevel(env('MONOLOG_DEFAULT_LEVEL', Logger::DEBUG)),

    /**
     * @see https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#handlers
     */
    'handlers' => [
        'default' => [
            $appLogHandler,
        ],
        'stderr' => [
            \Monolog\Handler\ErrorLogHandler::class,
        ],
        'stdout' => [
            [
                'class' => SyslogHandler::class,
                'options' => [
                    'ident' => 'app',
                    'facility' => LOG_USER,
                ],
            ],
        ],
    ],

    /**
     * Processors allows adding extra data for all records.
     *
     * @see https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md#processors
     */
    'processors' => [
        'default' => [
            \Spiral\Telemetry\Monolog\TelemetryProcessor::class,
            TraceIdProcessor::class,
        ],
        'stderr' => [
            PsrLogMessageProcessor::class,
            TraceIdProcessor::class,
        ],
        'stdout' => [
            [
                'class' => PsrLogMessageProcessor::class,
                'options' => [
                    'dateFormat' => 'Y-m-d\TH:i:s.uP',
                ],
            ],
            TraceIdProcessor::class,
        ],
        ErrorHandlerMiddleware::class => [
            PsrLogMessageProcessor::class,
            TraceIdProcessor::class,
        ],
        MySQLDriver::class => [
            PsrLogMessageProcessor::class,
            TraceIdProcessor::class,
        ],
        // Selected as the default channel in production via MONOLOG_DEFAULT_CHANNEL
        // (see infra/ansible/roles/compose-render/templates/api.env.tpl); registered by
        // Spiral\RoadRunnerBridge\Bootloader\LoggerBootloader, which only adds a handler,
        // not a processor — this channel would otherwise fall back to the implicit
        // PsrLogMessageProcessor-only default.
        'roadrunner' => [
            PsrLogMessageProcessor::class,
            TraceIdProcessor::class,
        ],
    ],
];
