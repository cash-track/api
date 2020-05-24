<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Annotation\Route;
use App\Database\User;
use App\Request\LoginRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Prototype\Traits\PrototypeTrait;

final class LoginController
{
    use PrototypeTrait;

    /**
     * @Route(action="/auth/login", verbs={"POST"})
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

        $token = $this->authService->authenticate($user);

        return $this->responseAuthenticated($token, $user);
    }

    /**
     * @param \Spiral\Auth\TokenInterface $token
     * @param \App\Database\User $user
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function responseAuthenticated(TokenInterface $token, User $user): ResponseInterface
    {
        return $this->response->json([
            'userID' => $user->id,
            'token' => $token->getID(),
        ], 200);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function responseAuthenticationFailure(): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Wrong email or password.',
        ], 400);
    }
}
