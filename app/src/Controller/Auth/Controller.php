<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Http\ResponseWrapper;

abstract class Controller
{
    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
    ) {
    }

    protected function responseTokens(TokenInterface $accessToken, TokenInterface $refreshToken): ResponseInterface
    {
        return $this->response->json([
            'accessToken' => $accessToken->getID(),
            'accessTokenExpiredAt' => $accessToken->getExpiresAt()?->format(DATE_RFC3339),
            'refreshToken' => $refreshToken->getID(),
            'refreshTokenExpiredAt' => $refreshToken->getExpiresAt()?->format(DATE_RFC3339),
        ], 200);
    }

    protected function responseTokensWithUser(TokenInterface $accessToken, TokenInterface $refreshToken, User $user): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->userView->head($user),
            'accessToken' => $accessToken->getID(),
            'accessTokenExpiredAt' => $accessToken->getExpiresAt()?->format(DATE_RFC3339),
            'refreshToken' => $refreshToken->getID(),
            'refreshTokenExpiredAt' => $refreshToken->getExpiresAt()?->format(DATE_RFC3339),
        ], 200);
    }

    protected function responseAuthenticationFailure(): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Wrong email or password.',
        ], 400);
    }

    protected function responseUnauthenticated(): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Authentication required.',
        ], 401);
    }
}
