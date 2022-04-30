<?php

namespace Tests\Traits;

trait InteractsWithMock
{
    protected function mock(string $class, array $methods, \Closure $setup, bool $constructorOff = true)
    {
        $mockBuilder = $this->getMockBuilder($class);

        if ($constructorOff) {
            $mockBuilder = $mockBuilder->disableOriginalConstructor();
        }

        if (count($methods)) {
            $mockBuilder = $mockBuilder->onlyMethods($methods);
        }

        $mock = $mockBuilder->getMock();

        $setup($mock);

        $this->getContainer()->bind($class, fn() => $mock);
    }

    public function callMethod($object, $name, array $args)
    {
        $class = new \ReflectionClass($object);

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
