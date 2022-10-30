<?php

declare(strict_types=1);

namespace App\Bootloader;

use Monolog\Logger;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Http\Middleware\ErrorHandlerMiddleware;
use Spiral\Monolog\Bootloader\MonologBootloader;
use Spiral\Monolog\Config\MonologConfig;
use Spiral\Boot\EnvironmentInterface;
use Cycle\Database\Driver\MySQL\MySQLDriver;

class LoggingBootloader extends Bootloader
{
    /**
     * Default log channel
     *
     * @see \Spiral\Monolog\Config\MonologConfig::DEFAULT_CHANNEL
     */
    const DEFAULT_CHANNEL = 'default';

    /**
     * @param \Spiral\Monolog\Bootloader\MonologBootloader $monolog
     */
    public function boot(MonologBootloader $monolog, EnvironmentInterface $env): void
    {
        $this->configureCommonHandlers($monolog);

        if ($env->get('DEBUG')) {
            $this->configureDebugHandlers($monolog);
        }
    }

    /**
     * @param \Spiral\Monolog\Bootloader\MonologBootloader $monolog
     */
    private function configureCommonHandlers(MonologBootloader $monolog): void
    {
        // app level errors
        $monolog->addHandler(self::DEFAULT_CHANNEL, $monolog->logRotate(
            directory('runtime') . 'logs/error.log',
            Logger::ERROR,
            25,
            false
        ));

        // http level errors
        $monolog->addHandler(ErrorHandlerMiddleware::class, $monolog->logRotate(
            directory('runtime') . 'logs/http.log'
        ));
    }

    /**
     * @param \Spiral\Monolog\Bootloader\MonologBootloader $monolog
     */
    private function configureDebugHandlers(MonologBootloader $monolog): void
    {
        // debug and info messages via global LoggerInterface
        $monolog->addHandler(self::DEFAULT_CHANNEL, $monolog->logRotate(
            directory('runtime') . 'logs/debug.log'
        ));

        // debug database queries
        $monolog->addHandler(MySQLDriver::class, $monolog->logRotate(
            directory('runtime') . 'logs/db.log'
        ));
    }
}
