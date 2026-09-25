<?php

declare(strict_types=1);

namespace App\Sentry;

use OpenTelemetry\API\Trace\Span;
use Sentry\Event;
use Sentry\EventHint;

/**
 * Tags events with the OTel trace id and a Tempo link; strips request bodies, cookies and
 * the gateway's renamed Cloudflare headers.
 */
final class BeforeSend
{
    public function __construct(private readonly string $tempoUrl = '')
    {
    }

    public function __invoke(Event $event, ?EventHint $hint = null): ?Event
    {
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
