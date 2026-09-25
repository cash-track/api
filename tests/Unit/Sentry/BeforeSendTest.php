<?php

declare(strict_types=1);

namespace Tests\Unit\Sentry;

use App\Redis\RedisUnavailableException;
use App\Sentry\BeforeSend;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionDataBag;
use Sentry\Frame;
use Sentry\Stacktrace;
use Tests\TestCase;

class BeforeSendTest extends TestCase
{
    private const string TEMPO = 'http://grafana/explore?q={trace_id}';

    public function testTagsService(): void
    {
        $event = (new BeforeSend())(Event::createEvent());

        $this->assertSame('api', $event?->getTags()['service']);
    }

    public function testStripsRequestBodyAndCookies(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'url' => 'https://api/auth/login',
            'method' => 'POST',
            'data' => ['password' => 'secret'],
            'cookies' => ['a' => 'b'],
        ]);

        $request = (new BeforeSend())($event)?->getRequest() ?? [];

        $this->assertArrayNotHasKey('data', $request);
        $this->assertArrayNotHasKey('cookies', $request);
        $this->assertSame('POST', $request['method']);
    }

    public function testStripsCfOriginalHeadersCaseInsensitively(): void
    {
        $event = Event::createEvent();
        $event->setRequest([
            'headers' => [
                'Cf-Original-Connecting-IP' => ['1.2.3.4'],
                'cf-original-forwarded-for' => ['5.6.7.8'],
                'User-Agent' => ['test-agent'],
            ],
        ]);

        $headers = (new BeforeSend())($event)?->getRequest()['headers'] ?? [];

        $this->assertArrayNotHasKey('Cf-Original-Connecting-IP', $headers);
        $this->assertArrayNotHasKey('cf-original-forwarded-for', $headers);
        $this->assertSame(['test-agent'], $headers['User-Agent']);
    }

    public function testStripsFrameArguments(): void
    {
        $frame = new Frame('login', 'a.php', 1, vars: ['password' => 'secret']);
        $event = Event::createEvent();
        $event->setStacktrace(new Stacktrace([$frame]));
        $event->setExceptions([new ExceptionDataBag(new \RuntimeException(), new Stacktrace([$frame]))]);

        $event = (new BeforeSend())($event);

        $this->assertSame([], $event?->getStacktrace()?->getFrames()[0]->getVars());
        $this->assertSame([], $event?->getExceptions()[0]->getStacktrace()?->getFrames()[0]->getVars());
    }

    public function testNoTraceTagWithoutActiveSpan(): void
    {
        $event = (new BeforeSend(self::TEMPO))(Event::createEvent());

        $this->assertArrayNotHasKey('trace_id', $event?->getTags() ?? []);
        $this->assertArrayNotHasKey('tempo', $event?->getContexts() ?? []);
    }

    public function testTagsTraceIdAndTempoLink(): void
    {
        $scope = Span::wrap(SpanContext::create(str_repeat('a', 32), str_repeat('b', 16)))->activate();

        try {
            $event = (new BeforeSend(self::TEMPO))(Event::createEvent());
        } finally {
            $scope->detach();
        }

        $this->assertSame(str_repeat('a', 32), $event?->getTags()['trace_id']);
        $this->assertSame(
            ['trace_id' => str_repeat('a', 32), 'url' => 'http://grafana/explore?q=' . str_repeat('a', 32)],
            $event?->getContexts()['tempo'],
        );
    }

    public function testNoTempoContextWhenTemplateEmpty(): void
    {
        $scope = Span::wrap(SpanContext::create(str_repeat('a', 32), str_repeat('b', 16)))->activate();

        try {
            $event = (new BeforeSend())(Event::createEvent());
        } finally {
            $scope->detach();
        }

        $this->assertSame(str_repeat('a', 32), $event?->getTags()['trace_id']);
        $this->assertArrayNotHasKey('tempo', $event?->getContexts() ?? []);
    }

    public function testSendsSameExceptionOnce(): void
    {
        $beforeSend = new BeforeSend();
        $exception = new \RuntimeException('boom');

        $this->assertNotNull($beforeSend(Event::createEvent(), EventHint::fromArray(['exception' => $exception])));
        $this->assertNull($beforeSend(Event::createEvent(), EventHint::fromArray(['exception' => $exception])));
        $this->assertNotNull($beforeSend(
            Event::createEvent(),
            EventHint::fromArray(['exception' => new \RuntimeException('boom')]),
        ));
    }

    public function testFingerprintsRedisOutageIntoOneIssue(): void
    {
        $beforeSend = new BeforeSend();

        $first = $beforeSend(Event::createEvent(), EventHint::fromArray([
            'exception' => new RedisUnavailableException('Connection refused'),
        ]));
        $second = $beforeSend(Event::createEvent(), EventHint::fromArray([
            'exception' => new RedisUnavailableException('read error on connection'),
        ]));

        $this->assertSame(['redis-unavailable'], $first?->getFingerprint());
        $this->assertSame(['redis-unavailable'], $second?->getFingerprint());
    }

    public function testDoesNotFingerprintOtherRedisExceptions(): void
    {
        $event = (new BeforeSend())(Event::createEvent(), EventHint::fromArray([
            'exception' => new \RedisException('WRONGTYPE'),
        ]));

        $this->assertSame([], $event?->getFingerprint());
    }
}
