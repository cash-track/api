<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use App\Request\RefreshTokenRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class RefreshController
{
    use PrototypeTrait, AuthResponses;

    /**
     * @Route(route="/auth/refresh", name="auth.refresh", methods="POST")
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \App\Request\RefreshTokenRequest $refreshTokenRequest
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function refresh(ServerRequestInterface $request, RefreshTokenRequest $refreshTokenRequest): ResponseInterface
    {
        $authContext = $this->refreshTokenService->getContextByRequest($request);

        /** @var \App\Database\User $user */
        $user = $authContext->getActor();

        if (! $user instanceof User) {
            return $this->responseUnauthenticated();
        }

        $refreshToken = $authContext->getToken();

        if (! $refreshToken instanceof TokenInterface) {
            return $this->responseUnauthenticated();
        }

        // TODO. Add to blacklist token $refreshToken->getID();
        // TODO. Add to blacklist token $refreshTokenRequest->getAccessToken();

        $accessToken = $this->authService->authenticate($user);
        $refreshToken = $this->refreshTokenService->authenticate($user);

        return $this->responseTokens($accessToken, $refreshToken);
    }
}
