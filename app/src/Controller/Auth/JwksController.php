<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\JwksService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

/**
 * Publishes the access token RSA public key as a JWK Set (RFC 7517) so third parties can verify
 * RS256-signed access tokens without contacting the API. Public, unauthenticated by design.
 */
final class JwksController
{
    public function __construct(
        private readonly ResponseWrapper $response,
        private readonly JwksService $jwksService,
    ) {
    }

    #[Route(route: '/.well-known/jwks.json', name: 'auth.jwks', methods: 'GET', group: 'unversioned')]
    public function index(): ResponseInterface
    {
        return $this->response->json($this->jwksService->getKeySet());
    }
}
