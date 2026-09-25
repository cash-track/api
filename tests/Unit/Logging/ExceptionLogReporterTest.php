<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Exception\AuthenticationRequiredException;
use App\Logging\ExceptionLogReporter;
use Psr\Log\LoggerInterface;
use Spiral\Http\Exception\ClientException\NotFoundException;
use Spiral\Http\Exception\ClientException\ServerErrorException;
use Spiral\Router\Exception\RouterException;
use Spiral\Sentry\Config\SentryConfig;
use Tests\TestCase;

class ExceptionLogReporterTest extends TestCase
{
    public static function clientErrorProvider(): array
    {
        return [
            [new NotFoundException()],
            [new RouterException('no route')],
            [new AuthenticationRequiredException()],
        ];
    }

    /**
     * @dataProvider clientErrorProvider
     */
    public function testClientErrorsAreDebug(\Throwable $exception): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->never())->method('error');
        $logger->expects($this->once())->method('debug');

        $this->reporter($logger)->report($exception);
    }

    public static function serverErrorProvider(): array
    {
        return [
            [new \RuntimeException('db down')],
            [new ServerErrorException()],
        ];
    }

    /**
     * @dataProvider serverErrorProvider
     */
    public function testServerErrorsAreError(\Throwable $exception): void
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith($exception::class . ': '), []);

        $this->reporter($logger)->report($exception);
    }

    private function reporter(LoggerInterface $logger): ExceptionLogReporter
    {
        return new ExceptionLogReporter($logger, $this->getContainer()->get(SentryConfig::class));
    }
}
