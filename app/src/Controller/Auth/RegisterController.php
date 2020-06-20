<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\RegisterRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class RegisterController
{
    use PrototypeTrait, AuthResponses;

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

        try {
            $this->emailConfirmationService->create($user);
        } catch (\Throwable $exception) {
            // TODO. Handle error
        }

        $accessToken = $this->authService->authenticate($user);
        $refreshToken = $this->refreshTokenService->authenticate($user);

        return $this->responseTokensWithUser($accessToken, $refreshToken, $user);
    }
}
