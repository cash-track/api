<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LogoutRequest;
use App\Service\Auth\AuthService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class LogoutController
{
    public function __construct(
        protected readonly AuthService $auth,
        protected readonly ResponseWrapper $response,
    ) {
    }

    #[Route(route: '/auth/logout', name: 'auth.logout', methods: 'POST', group: 'auth')]
    public function logout(LogoutRequest $request): ResponseInterface
    {
        $this->auth->logout($request->refreshToken);

        return $this->response->create(200);
    }
}
