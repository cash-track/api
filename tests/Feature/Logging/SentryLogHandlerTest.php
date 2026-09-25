<?php

declare(strict_types=1);

namespace Tests\Feature\Logging;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Spiral\Monolog\LogFactory;
use Tests\TestCase;

class SentryLogHandlerTest extends TestCase
{
    public function testErrorWithExceptionIsCapturedOnce(): void
    {
        $exception = new \RuntimeException('boom');

        $logger = $this->loggerWithHub(function (MockObject $hub) use ($exception): void {
            $hub->expects($this->once())->method('captureException')->with($this->identicalTo($exception));
        });

        $logger->error('Unable to do a thing', ['user_id' => 1, 'exception' => $exception]);
    }

    public function testErrorWithoutExceptionIsNotCaptured(): void
    {
        $logger = $this->loggerWithHub(function (MockObject $hub): void {
            $hub->expects($this->never())->method('captureException');
        });

        $logger->error('https://api/x caused the error 500 (boom) by client 1.2.3.4.');
    }

    public function testWarningWithExceptionIsNotCaptured(): void
    {
        $logger = $this->loggerWithHub(function (MockObject $hub): void {
            $hub->expects($this->never())->method('captureException');
        });

        $logger->warning('Retrying', ['exception' => new \RuntimeException('boom')]);
    }

    /**
     * Fresh LogFactory: the default logger is cached and built with the real hub at boot.
     */
    private function loggerWithHub(\Closure $setup): LoggerInterface
    {
        $hub = $this->getMockBuilder(HubInterface::class)->getMock();
        $hub->method('withScope')->willReturnCallback(fn (callable $callback) => $callback(new Scope()));
        $setup($hub);

        $this->getContainer()->removeBinding(HubInterface::class);
        $this->getContainer()->bindSingleton(HubInterface::class, $hub);

        return $this->getContainer()->make(LogFactory::class)->getLogger();
    }
}
