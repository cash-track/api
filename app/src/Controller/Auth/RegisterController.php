<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Annotation\Route;
use App\Request\RegisterRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;

class RegisterController
{
    use PrototypeTrait;

    /**
     * @Route(action="/auth/register", verbs={"POST"})
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
        $user = $this->authService->hashPassword($user, $request->getField('password'));

        try {
            $user = $this->userService->store($user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to register new user. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        // TODO. Send confirmation email

        // TODO. Authenticate newly user here

        return $this->response->json([
            'message' => 'ok',
            'data' => [
                'userId' => $user->id,
            ]
        ], 201);
    }
}
