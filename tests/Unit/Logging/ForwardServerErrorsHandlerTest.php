<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\ForwardServerErrorsHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class ForwardServerErrorsHandlerTest extends TestCase
{
    public function testForwards500ToLogger(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('caused the error 500 '), ['foo' => 'bar']);

        $handler = new ForwardServerErrorsHandler(fn (): LoggerInterface => $logger);

        $result = $handler->handle($this->makeRecord(
            'https://api/v1/x caused the error 500 (Database exception) by client 127.0.0.1.',
            ['foo' => 'bar'],
        ));

        $this->assertFalse($result);
    }

    public function testDoesNotForward404(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->never())->method('error');

        $handler = new ForwardServerErrorsHandler(fn (): LoggerInterface => $logger);

        $result = $handler->handle($this->makeRecord(
            'https://api/v1/x caused the error 404 (Not found) by client 127.0.0.1.',
        ));

        $this->assertFalse($result);
    }

    public function testDoesNotForward401(): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->never())->method('error');

        $handler = new ForwardServerErrorsHandler(fn (): LoggerInterface => $logger);

        $handler->handle($this->makeRecord(
            'https://api/v1/x caused the error 401 (Unauthorized) by client 127.0.0.1.',
        ));
    }

    public function testNeverResolvesLoggerWhenNotForwarding(): void
    {
        $handler = new ForwardServerErrorsHandler(function (): LoggerInterface {
            $this->fail('Logger should not be resolved for a non-5xx record.');
        });

        $handler->handle($this->makeRecord('unrelated debug line'));

        $this->addToAssertionCount(1);
    }

    private function makeRecord(string $message, array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'http',
            level: Level::Error,
            message: $message,
            context: $context,
        );
    }
}
