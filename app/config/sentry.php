<?php

declare(strict_types=1);

use App\Exception\AuthenticationRequiredException;
use App\Exception\UnconfirmedProfileException;
use App\Sentry\BeforeSend;
use Spiral\Http\Exception\ClientException;
use Spiral\Router\Exception\RouterException;

// Errors only: Tempo is the tracing system. Empty SENTRY_DSN disables sending.
return [
    'release' => env('GIT_TAG') ? 'api@' . (string) env('GIT_TAG') : null,
    'traces_sample_rate' => null,
    // Expected client-side outcomes, not bugs. ServerErrorException stays reported.
    'ignore_exceptions' => [
        RouterException::class,
        ClientException\BadRequestException::class,
        ClientException\UnauthorizedException::class,
        ClientException\ForbiddenException::class,
        ClientException\NotFoundException::class,
        AuthenticationRequiredException::class,
        UnconfirmedProfileException::class,
    ],
    'before_send' => new BeforeSend((string) env('SENTRY_TEMPO_URL', '')),
];
