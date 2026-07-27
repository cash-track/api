<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Spiral\Boot\EnvironmentInterface;

/**
 * Stamps every response with the build provenance of the running image, omitting either
 * header entirely when its source env var is empty or unset.
 */
final class ApiVersionMiddleware implements MiddlewareInterface
{
    private const string HEADER_VERSION = 'X-Ct-Api-Version';
    private const string HEADER_SHA = 'X-Ct-Api-Sha';

    private readonly ?string $apiVersion;
    private readonly ?string $apiSha;

    public function __construct(EnvironmentInterface $environment)
    {
        $this->apiVersion = $this->normalize($environment->get('GIT_TAG'));
        $this->apiSha = $this->normalize($environment->get('GIT_COMMIT'));
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->apiVersion !== null) {
            $response = $response->withHeader(self::HEADER_VERSION, $this->apiVersion);
        }

        if ($this->apiSha !== null) {
            $response = $response->withHeader(self::HEADER_SHA, $this->apiSha);
        }

        return $response;
    }

    private function normalize(mixed $value): ?string
    {
        if (!\is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
