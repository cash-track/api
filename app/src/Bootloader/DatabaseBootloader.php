<?php

declare(strict_types=1);

namespace App\Bootloader;

use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\Container;

class DatabaseBootloader extends Bootloader
{
    /**
     * @param \Spiral\Core\Container $container
     * @return void
     */
    public function boot(Container $container): void
    {
        $container->bind(DatabaseProviderInterface::class, DatabaseManager::class);
    }
}
