<?php

namespace Tests\Traits;

trait InteractsWithMock
{
    protected function mock(string $class, array $methods, \Closure $closure)
    {
        $mock = $this->getMockBuilder($class)
                     ->disableOriginalConstructor();

        if (count($methods)) {
            $mock = $mock->onlyMethods($methods);
        }

        $mock = $mock->getMock();

        $closure($mock);

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
