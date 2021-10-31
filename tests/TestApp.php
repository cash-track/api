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
}
