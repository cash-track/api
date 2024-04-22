<?php

declare(strict_types=1);

namespace Tests;

use Cycle\Database\DatabaseInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Core\Container;
use Spiral\Testing\TestableKernelInterface;
use Spiral\Testing\TestCase as BaseTestCase;
use Spiral\Translator\TranslatorInterface;
use Tests\App\TestKernel;
use Tests\Traits\AssertHelpers;
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
    use AssertHelpers;

    public const ENV = [
        'REDIS_CONNECTION' => '',
    ];

    protected function setUp(): void
    {
        $this->beforeBooting(static function (ConfiguratorInterface $config): void {
            if (! $config->exists('session')) {
                return;
            }

            $config->modify('session', new Set('handler', null));
        });

        parent::setUp();

        $this->getContainer()->get(TranslatorInterface::class)->setLocale('en');

        $this->scopedDatabase();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function scopedDatabase(): void
    {
        if (! $this instanceof DatabaseTransaction) {
            return;
        }

        /** @var \Cycle\Database\DatabaseInterface $db */
        $db = $this->getContainer()->get(DatabaseInterface::class);
        $db->begin();
    }

    protected function scopedDatabaseFinalise(): void
    {
        if (! $this instanceof DatabaseTransaction) {
            return;
        }

        /** @var \Cycle\Database\DatabaseInterface $db */
        $db = $this->getContainer()->get(DatabaseInterface::class);

        while ($db->getDriver()->getTransactionLevel() !== 0) {
            if (! $db->rollback()) {
                return;
            }
        }

        $db->getDriver()->disconnect();
    }

    protected function tearDown(): void
    {
        $this->scopedDatabaseFinalise();

        parent::tearDown();

        $container = $this->getContainer();

        if ($container instanceof Container) {
            $container->destruct();
        }

        unset($container);

        // Uncomment this line if you want to clean up runtime directory.
        // $this->cleanUpRuntimeDirectory();
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

    public function createAppInstance(Container $container = new Container()): TestableKernelInterface
    {
        return TestKernel::create(
            directories: $this->defineDirectories(
                $this->rootDirectory(),
            ),
            handleErrors: false,
            container: $container,
        );
    }
}
