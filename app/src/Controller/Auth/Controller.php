<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\Authentication;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Translator\Traits\TranslatorTrait;

abstract class Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
    ) {
    }

    protected function responseTokensWithUser(Authentication $authentication): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->userView->head($authentication->user),
            'accessToken' => $authentication->accessToken->getID(),
            'accessTokenExpiredAt' => $authentication->accessToken->getExpiresAt()?->format(DATE_RFC3339),
            'refreshToken' => $authentication->refreshToken->getID(),
            'refreshTokenExpiredAt' => $authentication->refreshToken->getExpiresAt()?->format(DATE_RFC3339),
        ], 200);
    }

    protected function responseAuthenticationFailure(string $error = '', ?string $message = null): ResponseInterface
    {
        return $this->response->json([
            'error' => $error,
            'message' => $message ?? $this->say('error_authentication_failure'),
        ], 400);
    }

    protected function responseUnauthenticated(): ResponseInterface
    {
        return $this->response->json([
            'message' => $this->say('error_authentication_required'),
        ], 401);
    }

    protected function responseAuthenticationException(string $error = '', ?string $message = null): ResponseInterface
    {
        return $this->response->json([
            'error' => $error,
            'message' => $message ?? $this->say('error_authentication_exception'),
        ], 500);
    }
}
