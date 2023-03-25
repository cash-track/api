<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LogoutRequest;
use App\Service\Auth\RefreshTokenService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Auth\TokenInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class LogoutController
{
    public function __construct(
        protected AuthScope $auth,
        protected ResponseWrapper $response,
        protected RefreshTokenService $refreshTokenService,
    ) {
    }

    #[Route(route: '/auth/logout', name: 'auth.logout', methods: 'POST', group: 'auth')]
    public function logout(LogoutRequest $request): ResponseInterface
    {
        $this->auth->close();

        $refreshToken = $this->refreshTokenService->getContextByToken($request->refreshToken)->getToken();

        if ($refreshToken instanceof TokenInterface) {
            $this->refreshTokenService->close($refreshToken);
        }

        return $this->response->create(200);
    }
}
