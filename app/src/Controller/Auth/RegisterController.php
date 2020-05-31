<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use App\Request\RegisterRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class RegisterController
{
    use PrototypeTrait;

    /**
     * @Route(route="/auth/register", name="auth.register", methods="POST")
     *
     * @param \App\Request\RegisterRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function register(RegisterRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $user = $request->createUser();
        $this->authService->hashPassword($user, $request->getField('password'));

        try {
            $user = $this->userService->store($user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to register new user. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        // TODO. Send confirmation email

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
            'data'  => $this->userView->head($user),
            'token' => $token->getID(),
        ], 201);
    }
}
