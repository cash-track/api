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
     * @param \App\Request\RegisterRequest $registerRequest
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function register(RegisterRequest $registerRequest): ResponseInterface
    {
        if (! $registerRequest->isValid()) {
            return $this->response->json([
                'errors' => $registerRequest->getErrors(),
            ], 422);
        }

        $user = $registerRequest->createUser();
        $user = $this->userService->hashPassword($user, $registerRequest->getField('password'));

        try {
            $user = $this->userService->store($user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to register new user. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        // TODO. Authenticate newly user here

        return $this->response->json([
            'message' => 'ok',
            'data' => [
                'userId' => $user->id,
            ]
        ], 201);
    }
}
