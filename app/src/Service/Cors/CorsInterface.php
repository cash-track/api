<?php

declare(strict_types=1);

namespace App\Service\Cors;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CorsInterface
{
    /**
     * Check if given request are browser preflight request
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    public function isPreflightRequest(ServerRequestInterface $request): bool;

    /**
     * Create preflight response with needle CORS headers
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface;

    /**
     * Update given response with needle CORS headers
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handleActualRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}