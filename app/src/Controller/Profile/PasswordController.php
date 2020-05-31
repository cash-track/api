<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Request\Profile\UpdatePasswordRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class PasswordController
{
    use PrototypeTrait;

    /**
     * @Route(route="/profile/password", name="profile.update.password", methods="PUT", group="auth")
     *
     * @param \App\Request\Profile\UpdatePasswordRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(UpdatePasswordRequest $request): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $request->setContext($user);

        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $this->authService->hashPassword($user, $request->getNewPassword());

        try {
            $this->userService->store($user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user', [
                'action' => 'profile.update.password',
                'userId' => $user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update user password. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        // TODO. End here all active sessions except current.

        // TODO. Add active token to blacklist, generate new and add to response.

        return $this->response->json([
            'message' => 'Password has been changed.'
        ], 200);
    }
}
