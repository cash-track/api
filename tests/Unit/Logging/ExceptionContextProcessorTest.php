<?php

declare(strict_types=1);

namespace Tests\Unit\Logging;

use App\Logging\ExceptionContextProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class ExceptionContextProcessorTest extends TestCase
{
    public function testAddsReadableErrorAndKeepsException(): void
    {
        $exception = new \RuntimeException('boom');

        $record = (new ExceptionContextProcessor())($this->makeRecord(['exception' => $exception]));

        $this->assertSame($exception, $record->context['exception']);
        $this->assertStringStartsWith('RuntimeException: boom in ', $record->context['error']);
    }

    public function testKeepsExplicitError(): void
    {
        $record = (new ExceptionContextProcessor())($this->makeRecord([
            'exception' => new \RuntimeException('boom'),
            'error' => 'custom',
        ]));

        $this->assertSame('custom', $record->context['error']);
    }

    public function testIgnoresRecordWithoutThrowable(): void
    {
        $record = (new ExceptionContextProcessor())($this->makeRecord(['exception' => 'not a throwable']));

        $this->assertArrayNotHasKey('error', $record->context);
    }

    private function makeRecord(array $context): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'default',
            level: Level::Error,
            message: 'test',
            context: $context,
        );
    }
}
