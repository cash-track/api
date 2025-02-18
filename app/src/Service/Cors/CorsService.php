<?php

declare(strict_types=1);

namespace App\Service\Cors;

use App\Config\CorsConfig;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CorsService implements CorsInterface
{
    public function __construct(
        private readonly CorsConfig $config,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[\Override]
    public function isPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    #[\Override]
    public function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->addPreflightRequestHeaders($request, $this->responseFactory->createResponse(204));
        return $this->varyHeader($response, 'Access-Control-Request-Method');
    }

    #[\Override]
    public function handleActualRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->addActualRequestHeaders($request, $response);

        if ($request->getMethod() === 'OPTIONS') {
            $response = $this->varyHeader($response, 'Access-Control-Request-Method');
        }

        return $response;
    }

    protected function addPreflightRequestHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowedCredentials($request, $response);

            $response = $this->configureAllowedMethods($request, $response);

            $response = $this->configureAllowedHeaders($request, $response);

            $response = $this->configureMaxAge($request, $response);
        }

        return $response;
    }

    protected function addActualRequestHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->configureAllowedOrigin($request, $response);

        if ($response->hasHeader('Access-Control-Allow-Origin')) {
            $response = $this->configureAllowedCredentials($request, $response);

            $response = $this->configureExposeHeaders($request, $response);
        }

        return $response;
    }

    protected function configureAllowedOrigin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->isAllowedAllOrigins() && !$this->config->getSupportsCredentials()) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        } elseif ($this->isSingleOriginAllowed()) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $this->config->getAllowedOrigins()[0]);
        } else {
            if ($this->isOriginAllowed($request)) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $request->getHeader('Origin'));
            }

            $response = $this->varyHeader($response, 'Origin');
        }

        return $response;
    }

    protected function configureAllowedCredentials(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (! $this->config->getSupportsCredentials()) {
            return $response;
        }

        return $response->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    protected function configureAllowedMethods(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->isAllowedAllMethods()) {
            $methods = strtoupper($request->getHeaderLine('Access-Control-Request-Method'));
            $response = $this->varyHeader($response, 'Access-Control-Request-Method');
        } else {
            $methods = $this->config->getAllowedMethods();
        }

        return $response->withHeader('Access-Control-Allow-Methods', $methods);
    }

    protected function configureAllowedHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->isAllowedAllHeaders()) {
            $headers = $request->getHeaderLine('Access-Control-Request-Headers');
            $response = $this->varyHeader($response, 'Access-Control-Request-Headers');
        } else {
            $headers = $this->config->getAllowedHeaders();
        }

        return $response->withHeader('Access-Control-Allow-Headers', $headers);
    }

    protected function configureMaxAge(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->config->getMaxAge() === 0) {
            return $response;
        }

        return $response->withHeader('Access-Control-Max-Age', (string) $this->config->getMaxAge());
    }

    protected function configureExposeHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (count($this->config->getExposedHeaders()) === 0) {
            return $response;
        }

        return $response->withHeader('Access-Control-Expose-Headers', $this->config->getExposedHeaders());
    }

    protected function isSingleOriginAllowed(): bool
    {
        if ($this->isAllowedAllOrigins() || count($this->config->getAllowedOriginsPatterns()) > 0) {
            return false;
        }

        return count($this->config->getAllowedOrigins()) === 1;
    }

    protected function isOriginAllowed(ServerRequestInterface $request): bool
    {
        if ($this->isAllowedAllOrigins()) {
            return true;
        }

        if (!$request->hasHeader('Origin')) {
            return false;
        }

        $origin = $request->getHeaderLine('Origin');

        if (in_array($origin, $this->config->getAllowedOrigins())) {
            return true;
        }

        foreach ($this->config->getAllowedOriginsPatterns() as $pattern) {
            if (preg_match($this->convertPatternToRegex($pattern), $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $pattern
     * @return non-empty-string
     */
    protected function convertPatternToRegex(string $pattern): string
    {
        $pattern = str_replace(['/', '.', '*'], ['\/', '\.', '.*'], $pattern);

        return "/{$pattern}/";
    }

    protected function isAllowedAllOrigins(): bool
    {
        return in_array('*', $this->config->getAllowedOrigins());
    }

    protected function isAllowedAllHeaders(): bool
    {
        return in_array('*', $this->config->getAllowedHeaders());
    }

    protected function isAllowedAllMethods(): bool
    {
        return in_array('*', $this->config->getAllowedMethods());
    }

    protected function varyHeader(ResponseInterface $response, string $value): ResponseInterface
    {
        return $response->withAddedHeader('Vary', $value);
    }
}
