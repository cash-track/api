<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\Environment;
use Spiral\Database\DatabaseInterface;
use Spiral\Files\Files;
use Spiral\Http\Http;
use Spiral\Translator\TranslatorInterface;
use Spiral\Views\ViewsInterface;
use Tests\Traits\InteractsWithConsole;
use Tests\Traits\InteractsWithDatabase;
use Tests\Traits\InteractsWithHttp;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithConsole;
    use InteractsWithHttp;
    use InteractsWithDatabase;

    /**
     * @var \Tests\TestApp
     */
    protected $app;

    /**
     * @var \Spiral\Http\Http
     */
    protected $http;

    /**
     * @var \Spiral\Views\ViewsInterface
     */
    protected $views;

    /**
     * @var \Spiral\Database\DatabaseInterface
     */
    protected DatabaseInterface $db;

    protected function setUp(): void
    {
        $this->app = $this->makeApp();
        $this->http = $this->app->get(HTTP::class);
        $this->views = $this->app->get(ViewsInterface::class);
        $this->db = $this->app->get(DatabaseInterface::class);
        $this->app->get(TranslatorInterface::class)->setLocale('en');

        if ($this instanceof DatabaseTransaction) {
            $this->db->begin();
        }
    }

    protected function tearDown(): void
    {
        if ($this instanceof DatabaseTransaction) {
            $this->db->rollback();
        }

        $fs = new Files();

        $runtime = $this->app->get(DirectoriesInterface::class)->get('runtime');
        if ($fs->isDirectory($runtime)) {
            $fs->deleteDirectory($runtime);
        }
    }

    protected function makeApp(array $env = []): TestApp
    {
        $root = dirname(__DIR__);

        return TestApp::init([
            'root' => $root,
            'app' => $root . '/app',
            'runtime' => $root . '/runtime/tests',
            'cache' => $root . '/runtime/tests/cache',
        ], new Environment($env), false);
    }
}
