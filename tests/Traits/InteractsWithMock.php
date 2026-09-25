<?php

namespace Tests\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

    /**
     * Expects one error log with an exception (reaches Sentry), or no error log at all when $message is null.
     */
    protected function expectErrorLog(?string $message): void
    {
        $this->mock(LoggerInterface::class, [], function (MockObject $mock) use ($message): void {
            if ($message === null) {
                $mock->expects($this->never())->method('error');
                return;
            }

            $mock->expects($this->once())->method('error')->with($message, $this->callback(
                fn (array $context): bool => ($context['exception'] ?? null) instanceof \Throwable,
            ));
        });
    }

    public function callMethod($object, $name, array $args)
    {
        $class = new \ReflectionClass($object);

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
