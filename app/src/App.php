<?php

declare(strict_types=1);

namespace App;

use App\Bootloader;
use Spiral\Bootloader as Framework;
use Spiral\DotEnv\Bootloader as DotEnv;
use Spiral\Framework\Kernel;
use Spiral\Monolog\Bootloader as Monolog;
use Spiral\Nyholm\Bootloader as Nyholm;
use Spiral\Prototype\Bootloader as Prototype;
use Spiral\Router\Bootloader as Router;
use Spiral\Scaffolder\Bootloader as Scaffolder;
use Spiral\Stempler\Bootloader as Stempler;

class App extends Kernel
{
    /*
     * List of components and extensions to be automatically registered
     * within system container on application start.
     */
    protected const LOAD = [
        // Base extensions
        DotEnv\DotenvBootloader::class,
        Monolog\MonologBootloader::class,

        // Application specific logs
        Bootloader\LoggingBootloader::class,

        // Core Services
        Framework\SnapshotsBootloader::class,
        Framework\I18nBootloader::class,

        // Security and validation
        Framework\Security\EncrypterBootloader::class,
        Framework\Security\ValidationBootloader::class,
        Framework\Security\FiltersBootloader::class,
        Framework\Security\GuardBootloader::class,

        // HTTP extensions
        Nyholm\NyholmBootloader::class,
        Framework\Http\RouterBootloader::class,
        Router\AnnotatedRoutesBootloader::class,
        Framework\Http\ErrorHandlerBootloader::class,
        Framework\Http\JsonPayloadsBootloader::class,
        Framework\Http\CookiesBootloader::class,
        Framework\Http\SessionBootloader::class,
        Framework\Http\CsrfBootloader::class,
        Framework\Http\PaginationBootloader::class,

        // Databases
        Framework\Database\DatabaseBootloader::class,
        Framework\Database\MigrationsBootloader::class,

        // ORM
        Framework\Cycle\CycleBootloader::class,
        Framework\Cycle\ProxiesBootloader::class,
        Framework\Cycle\AnnotatedBootloader::class,

        // Views and view translation
        Framework\Views\ViewsBootloader::class,
        Framework\Views\TranslatedCacheBootloader::class,

        // Additional dispatchers
        Framework\Jobs\JobsBootloader::class,

        // Extensions and bridges
        Stempler\StemplerBootloader::class,

        // Framework commands
        Framework\CommandBootloader::class,
        Scaffolder\ScaffolderBootloader::class,

        // Debug and debug extensions
        Framework\DebugBootloader::class,
        Framework\Debug\LogCollectorBootloader::class,
        Framework\Debug\HttpCollectorBootloader::class,

        // Authentication
        Framework\Auth\HttpAuthBootloader::class,
        Auth\Jwt\TokensBootloader::class,

        Service\Pagination\PaginationBootloader::class,
    ];

    /*
     * Application specific services and extensions.
     */
    protected const APP = [
        Auth\AuthBootloader::class,
        Bootloader\RouteGroupsBootloader::class,
        Bootloader\UserBootloader::class,
        Bootloader\LocaleSelectorBootloader::class,
        Bootloader\CheckerBootloader::class,

        Bootloader\FirebaseBootloader::class,
        Bootloader\S3Bootloader::class,
        Bootloader\MailerBootloader::class,

        // fast code prototyping
        Prototype\PrototypeBootloader::class,
    ];
}
