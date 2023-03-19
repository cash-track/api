<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\TokenInterface;

trait AuthResponses
{
    /**
     * @param \Spiral\Auth\TokenInterface $accessToken
     * @param \Spiral\Auth\TokenInterface $refreshToken
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function responseTokens(TokenInterface $accessToken, TokenInterface $refreshToken): ResponseInterface
    {
        return $this->response->json([
            'accessToken' => $accessToken->getID(),
            'accessTokenExpiredAt' => $accessToken->getExpiresAt()?->format(DATE_RFC3339),
            'refreshToken' => $refreshToken->getID(),
            'refreshTokenExpiredAt' => $refreshToken->getExpiresAt()?->format(DATE_RFC3339),
        ], 200);
    }

    /**
     * @param \Spiral\Auth\TokenInterface $accessToken
     * @param \Spiral\Auth\TokenInterface $refreshToken
     * @param \App\Database\User $user
     * @return \Psr\Http\Message\ResponseInterface
     */
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

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function responseAuthenticationFailure(): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Wrong email or password.',
        ], 400);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function responseUnauthenticated(): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Authentication required.',
        ], 401);
    }
}
