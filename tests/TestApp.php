<?php

declare(strict_types=1);

namespace Tests;

use App\App;

class TestApp extends App
{
    /**
     * @var \Spiral\Core\Container
     */
    public $container;

    /**
     * @var \Tests\TestApp|null
     */
    protected static $app;

    /**
     * @var bool
     */
    public static $reuseApp = true;

    /**
     * @var array
     */
    protected static array $bootLoadedState = [
        'bindings' => [],
        'injectors' => [],
    ];

    /**
     * Get object from the container.
     *
     * @param string      $alias
     * @param string|null $context
     * @return mixed|object|null
     * @throws \Throwable
     */
    public function get($alias, string $context = null)
    {
        return $this->container->get($alias, $context);
    }

    /**
     * @param callable $resolver
     * @return \Tests\TestApp
     */
    public static function getInstance(callable $resolver): TestApp
    {
        if (! self::$reuseApp) {
            return $resolver();
        }

        if (self::$app instanceof self) {
            return self::$app->flush();
        }

        $core = $resolver();

        if (! $core instanceof TestApp) {
            throw new \RuntimeException('Resolver must instantiate TestApp instance.');
        }

        return self::$app = $core->backup();
    }

    /**
     * Clear any mutation made on app instance during test case
     *
     * @return $this
     */
    protected function flush(): TestApp
    {
        $this->finalizer->finalize();

        $this->flushContainer();
        $this->restoreContainer();

        return $this;
    }

    /**
     * Save bootloaded app state for future usage
     *
     * @return $this
     */
    protected function backup(): TestApp
    {
        self::$bootLoadedState['bindings'] = $this->container->getBindings();
        self::$bootLoadedState['injectors'] = $this->container->getInjectors();

        return $this;
    }

    /**
     * Prepare container to restore backup state
     *
     * @return void
     */
    public function flushContainer(): void
    {
        foreach ($this->container->getBindings() as $alias => $_) {
            $this->container->removeBinding($alias);
        }

        foreach ($this->container->getInjectors() as $class => $_) {
            $this->container->removeInjector($class);
        }
    }

    /**
     * Restore container state from saved backup
     */
    protected function restoreContainer(): void
    {
        foreach (self::$bootLoadedState['bindings'] as $alias => $binding) {
            if (is_array($binding) && ($binding[1] ?? null) === true) {
                $this->container->bindSingleton($alias, $binding[0]);
                continue;
            }

            if (is_object($binding)) {
                $this->container->bindSingleton($alias, $binding);
                continue;
            }

            if (is_array($binding) && ($binding[1] ?? null) === false) {
                $this->container->bind($alias, $binding[0]);
                continue;
            }

            $this->container->bind($alias, $binding);
        }

        foreach (self::$bootLoadedState['injectors'] as $alias => $injector) {
            $this->container->bindInjector($alias, $injector);
        }
    }
}
