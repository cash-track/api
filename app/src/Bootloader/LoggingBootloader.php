<?php

declare(strict_types=1);

namespace App\Bootloader;

use App\Logging\ForwardServerErrorsHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;
use Spiral\Http\Middleware\ErrorHandlerMiddleware;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiral\Boot\EnvironmentInterface;
use Cycle\Database\Driver\MySQL\MySQLDriver;

final class LoggingBootloader extends Bootloader
{
    /**
     * Default log channel
     *
     * @see \Spiral\Monolog\Config\MonologConfig::DEFAULT_CHANNEL
     */
    const string DEFAULT_CHANNEL = 'default';

    /**
     * MonologBootloader::logRotate()'s own default format has no %extra% placeholder, which
     * would silently drop trace_id (written there by App\Logging\TraceIdProcessor). Applied to
     * every rotating file handler below via withTraceableFormat(), and reused by
     * app/config/monolog.php for the app.log handler so both stay in sync.
     */
    public const string LOG_FORMAT = "[%datetime%] %level_name%: %message% %context% %extra%\n";

    public function init(MonologBootloader $monolog, EnvironmentInterface $env, Container $container): void
    {
        $this->configureCommonHandlers($monolog, $container);

        if ($env->get('DEBUG')) {
            $this->configureDebugHandlers($monolog);
        }
    }

    private function configureCommonHandlers(MonologBootloader $monolog, Container $container): void
    {
        // app level errors
        $monolog->addHandler(
            channel: self::DEFAULT_CHANNEL,
            handler: $this->withTraceableFormat($monolog->logRotate(
                filename: directory('runtime') . 'logs/error.log',
                level: Level::Error,
                maxFiles: 25,
                bubble: false
            ))
        );

        // http level errors
        $monolog->addHandler(
            channel: ErrorHandlerMiddleware::class,
            handler: $this->withTraceableFormat($monolog->logRotate(
                filename: directory('runtime') . 'logs/http.log'
            ))
        );

        // 5xx only, forwarded to the default channel so they reach stdout/Loki in prod.
        // Resolved lazily: the container/Monolog config may still be assembling right now.
        $monolog->addHandler(
            channel: ErrorHandlerMiddleware::class,
            handler: new ForwardServerErrorsHandler(
                static fn (): LoggerInterface => $container->get(LoggerInterface::class),
            ),
        );
    }

    private function configureDebugHandlers(MonologBootloader $monolog): void
    {
        // debug and info messages via global LoggerInterface
        $monolog->addHandler(
            channel: self::DEFAULT_CHANNEL,
            handler: $this->withTraceableFormat($monolog->logRotate(
                filename: directory('runtime') . 'logs/debug.log'
            ))
        );

        // debug database queries
        $monolog->addHandler(
            channel: MySQLDriver::class,
            handler: $this->withTraceableFormat($monolog->logRotate(
                filename: directory('runtime') . 'logs/db.log'
            ))
        );
    }

    /**
     * Overrides the formatter MonologBootloader::logRotate() already applied, so trace_id
     * survives into these rotating log files. The handlers logRotate() returns are always
     * FormattableHandlerInterface in practice; the instanceof guard keeps this safe against
     * its HandlerInterface return type without assuming the concrete class.
     */
    private function withTraceableFormat(HandlerInterface $handler): HandlerInterface
    {
        if ($handler instanceof FormattableHandlerInterface) {
            return $handler->setFormatter(new LineFormatter(self::LOG_FORMAT));
        }

        return $handler;
    }
}
