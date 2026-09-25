<?php

declare(strict_types=1);

namespace Tests\Unit\Sentry;

use App\Sentry\BeforeSend;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use Sentry\Event;
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
}
