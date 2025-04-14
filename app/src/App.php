<?php

declare(strict_types=1);

namespace App;

use Spiral\Boot\Bootloader\CoreBootloader;
use Spiral\Bootloader as Framework;
use Spiral\Bootloader\Views\TranslatedCacheBootloader;
use Spiral\Cache\Bootloader\CacheBootloader;
use Spiral\Distribution\Bootloader\DistributionBootloader;
use Spiral\DotEnv\Bootloader\DotenvBootloader;
use Spiral\Events\Bootloader\EventsBootloader;
use Spiral\Framework\Kernel;
use Spiral\Filters\Bootloader\FiltersBootloader;
use Spiral\League\Event\Bootloader\EventBootloader;
use Spiral\Monolog\Bootloader as Monolog;
use Spiral\Nyholm\Bootloader\NyholmBootloader;
use Spiral\OpenTelemetry\Bootloader\OpenTelemetryBootloader;
use Spiral\Prototype\Bootloader as Prototype;
use Spiral\Queue\Bootloader\QueueBootloader;
use Spiral\Router\Bootloader as Router;
use Spiral\Scaffolder\Bootloader\ScaffolderBootloader;
use Spiral\Scheduler\Bootloader\SchedulerBootloader;
use Spiral\SendIt\Bootloader\MailerBootloader;
use Spiral\Cycle\Bootloader as CycleBridge;
use Spiral\RoadRunnerBridge\Bootloader as RoadRunnerBridge;
use Spiral\Stempler\Bootloader\StemplerBootloader;
use Spiral\Storage\Bootloader\StorageBootloader;
use Spiral\Tokenizer\Bootloader\TokenizerListenerBootloader;
use Spiral\Validation\Bootloader\ValidationBootloader;
use Spiral\Validator\Bootloader\ValidatorBootloader;
use Spiral\Views\Bootloader\ViewsBootloader;

class App extends Kernel
{
    protected const array SYSTEM = [
        CoreBootloader::class,
        TokenizerListenerBootloader::class,
        DotenvBootloader::class,
    ];

    /*
     * List of components and extensions to be automatically registered
     * within system container on application start.
     */
    protected const array LOAD = [
        OpenTelemetryBootloader::class,

        // Logging and exceptions handling
        RoadRunnerBridge\LoggerBootloader::class,
        Bootloader\LoggingBootloader::class,
        Monolog\MonologBootloader::class,
        Bootloader\ExceptionHandlerBootloader::class,

        // RoadRunner
        RoadRunnerBridge\QueueBootloader::class,
        RoadRunnerBridge\HttpBootloader::class,
        RoadRunnerBridge\CacheBootloader::class,
        RoadRunnerBridge\GRPCBootloader::class,

        // Core Services
        Framework\SnapshotsBootloader::class,

        // Security and validation
        Framework\Security\EncrypterBootloader::class,
        Framework\Security\FiltersBootloader::class,
        Framework\Security\GuardBootloader::class,

        // HTTP extensions
        Framework\Http\RouterBootloader::class,
        Framework\Http\JsonPayloadsBootloader::class,
        Framework\Http\CookiesBootloader::class,
        Framework\Http\SessionBootloader::class,
        Framework\Http\CsrfBootloader::class,
        Framework\Http\PaginationBootloader::class,

        Bootloader\CorsBootloader::class,
        Router\AnnotatedRoutesBootloader::class,

        // Databases
        CycleBridge\DatabaseBootloader::class,
        CycleBridge\MigrationsBootloader::class,

        // ORM
        CycleBridge\SchemaBootloader::class,
        CycleBridge\CycleOrmBootloader::class,
        CycleBridge\AnnotatedBootloader::class,
        CycleBridge\ValidationBootloader::class,
        Bootloader\EntityBehaviorBootloader::class,

        // Event Dispatcher
        EventsBootloader::class,
        EventBootloader::class,

        // Scheduler
        SchedulerBootloader::class,

        // Views and view translation
        ViewsBootloader::class,
        TranslatedCacheBootloader::class,
        StemplerBootloader::class,
        Framework\I18nBootloader::class,

        // Queue
        QueueBootloader::class,

        // Cache
        CacheBootloader::class,

        // Mailer
        MailerBootloader::class,

        // Storage
        StorageBootloader::class,
        DistributionBootloader::class,

        ValidationBootloader::class,
        ValidatorBootloader::class,

        RoadRunnerBridge\MetricsBootloader::class,

        NyholmBootloader::class,

        // Console commands
        Framework\CommandBootloader::class,
        RoadRunnerBridge\CommandBootloader::class,
        CycleBridge\CommandBootloader::class,
        ScaffolderBootloader::class,
        CycleBridge\ScaffolderBootloader::class,

        // Debug and debug extensions
        Framework\DebugBootloader::class,
        Framework\Debug\LogCollectorBootloader::class,
        Framework\Debug\HttpCollectorBootloader::class,

        // Authentication
        Framework\Auth\HttpAuthBootloader::class,
        Auth\Jwt\TokensBootloader::class,

        Service\Pagination\PaginationBootloader::class,
        FiltersBootloader::class,
    ];

    /*
     * Application specific services and extensions.
     */
    protected const array APP = [
        Bootloader\AppBootloader::class,
        Bootloader\RedisBootloader::class,
        Auth\AuthBootloader::class,
        Bootloader\RoutesBootloader::class,
        Bootloader\UserBootloader::class,
        Bootloader\CheckerBootloader::class,

        Bootloader\FirebaseBootloader::class,
        Bootloader\GoogleApiBootloader::class,
        Bootloader\S3Bootloader::class,
        Bootloader\MailerBootloader::class,

        // fast code prototyping
        Prototype\PrototypeBootloader::class,
    ];
}
