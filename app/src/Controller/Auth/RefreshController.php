<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\RefreshTokenRequest;
use App\Service\Auth\AuthService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class RefreshController extends Controller
{
    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        protected readonly AuthService $authService,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/refresh', name: 'auth.refresh', methods: 'POST')]
    public function refresh(RefreshTokenRequest $refreshTokenRequest): ResponseInterface
    {
        $auth = $this->authService->refresh($refreshTokenRequest->refreshToken);

        if ($auth === null) {
            return $this->responseUnauthenticated();
        }

        return $this->responseTokensWithUser($auth);
    }
}
