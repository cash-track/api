<?php

namespace Tests\Traits;

trait InteractsWithMock
{
    protected function mock(string $class, array $methods, \Closure $closure)
    {
        $mock = $this->getMockBuilder($class)
                     ->disableOriginalConstructor()
                     ->onlyMethods($methods)
                     ->getMock();

        $closure($mock);

        $this->getContainer()->bind($class, fn() => $mock);
    }
}
