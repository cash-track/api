<?php

declare(strict_types=1);

namespace Tests;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Core\Container;
use Spiral\Testing\TestableKernelInterface;
use Spiral\Testing\TestCase as BaseTestCase;
use Spiral\Translator\TranslatorInterface;
use Tests\App\TestApp;
use Tests\Traits\InteractsWithDatabase;
use Tests\Traits\InteractsWithHttp;
use Tests\Traits\InteractsWithMock;
use Tests\Traits\ProvideAuth;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithHttp;
    use InteractsWithDatabase;
    use ProvideAuth;
    use InteractsWithMock;

    protected function setUp(): void
    {
        $this->beforeStarting(static function (ConfiguratorInterface $config): void {
            if (! $config->exists('session')) {
                return;
            }

            $config->modify('session', new Set('handler', null));
        });

        parent::setUp();

        $this->getContainer()->get(TranslatorInterface::class)->setLocale('en');

        if ($this instanceof DatabaseTransaction) {
            $this->getContainer()->get(DatabaseInterface::class)->begin();
        }
    }

    protected function tearDown(): void
    {
        if ($this instanceof DatabaseTransaction) {
            $this->getContainer()->get(DatabaseInterface::class)->rollback();
        }

        // Uncomment this line if you want to clean up runtime directory.
        // $this->cleanUpRuntimeDirectory();

        foreach ($this->getContainer()->get(DatabaseManager::class)->getDrivers() as $driver) {
            $driver->disconnect();
        }
    }

    public function rootDirectory(): string
    {
        return __DIR__.'/..';
    }

    public function defineDirectories(string $root): array
    {
        return [
            'root' => $root,
        ];
    }

    public function createAppInstance(): TestableKernelInterface
    {
        return new TestApp(new Container(), $this->defineDirectories(
            $this->rootDirectory()
        ));
    }

}
