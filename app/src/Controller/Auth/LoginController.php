<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use App\Request\LoginRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class LoginController
{
    use PrototypeTrait, AuthResponses;

    /**
     * @Route(route="/auth/login", name="auth.login", methods="POST")
     *
     * @param \App\Request\LoginRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function login(LoginRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $user = $this->users->findByEmail($request->getField('email'));
        } catch (\Throwable $exception) {
            return $this->response->json([
                'error' => $exception->getMessage(),
                'message' => 'Unable to authenticate. Please try again later',
            ], 500);
        }

        if (! $user instanceof User) {
            return $this->responseAuthenticationFailure();
        }

        if (! $this->authService->verifyPassword($user, $request->getField('password'))) {
            return $this->responseAuthenticationFailure();
        }

        $accessToken = $this->authService->authenticate($user);
        $refreshToken = $this->refreshTokenService->authenticate($user);

        return $this->responseTokensWithUser($accessToken, $refreshToken, $user);
    }
}
