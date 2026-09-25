<?php

declare(strict_types=1);

namespace App\Sentry;

use App\Redis\RedisUnavailableException;
use OpenTelemetry\API\Trace\Span;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Tags events with the OTel trace id and a Tempo link; strips request bodies, cookies,
 * stack frame arguments and the gateway's renamed Cloudflare headers. Sends each exception
 * instance once.
 */
final class BeforeSend
{
    /** @var \WeakMap<\Throwable, true> */
    private \WeakMap $sent;

    public function __construct(private readonly string $tempoUrl = '')
    {
        /** @var \WeakMap<\Throwable, true> */
        $this->sent = new \WeakMap();
    }

    public function __invoke(Event $event, ?EventHint $hint = null): ?Event
    {
        // The same exception can be logged, reported by a failed queue job and reported by
        // ErrorHandlerMiddleware (sync queue).
        $exception = $hint?->exception;
        if ($exception !== null) {
            if (isset($this->sent[$exception])) {
                return null;
            }

            $this->sent[$exception] = true;

            // One Sentry issue per Redis outage, whatever the underlying connect error.
            if ($exception instanceof RedisUnavailableException) {
                $event->setFingerprint(['redis-unavailable']);
            }
        }

        $event->setTag('service', 'api');

        // Bodies can carry passwords and tokens.
        $request = $event->getRequest();
        unset($request['data'], $request['cookies']);

        // The gateway renames every Cf-* header to Cf-Original-*, including the client IP;
        // the SDK's PII header filter only knows the original names.
        foreach (array_keys($request['headers'] ?? []) as $name) {
            if (str_starts_with(strtolower((string) $name), 'cf-original-')) {
                unset($request['headers'][$name]);
            }
        }

        $event->setRequest($request);

        // Frame args can carry passwords, tokens and emails (zend.exception_ignore_args is Off).
        $stacktraces = [$event->getStacktrace()];
        foreach ($event->getExceptions() as $bag) {
            $stacktraces[] = $bag->getStacktrace();
        }
        foreach ($stacktraces as $stacktrace) {
            foreach ($stacktrace?->getFrames() ?? [] as $frame) {
                $frame->setVars([]);
            }
        }

        $context = Span::getCurrent()->getContext();
        if (!$context->isValid()) {
            return $event;
        }

        $traceId = $context->getTraceId();
        $event->setTag('trace_id', $traceId);

        if ($this->tempoUrl !== '') {
            $event->setContext('tempo', [
                'trace_id' => $traceId,
                'url' => str_replace('{trace_id}', $traceId, $this->tempoUrl),
            ]);
        }

        return $event;
    }
}
