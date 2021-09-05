<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Request\Profile\UpdatePasswordRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class PasswordController extends ProfileController
{
    use PrototypeTrait;

    /**
     * @Route(route="/profile/password", name="profile.update.password", methods="PUT", group="auth")
     *
     * @param \App\Request\Profile\UpdatePasswordRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function updatePassword(UpdatePasswordRequest $request): ResponseInterface
    {
        $request->setContext($this->user);

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $this->authService->hashPassword($this->user, $request->getNewPassword());

        try {
            $this->userService->store($this->user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user', [
                'action' => 'profile.update.password',
                'userId' => $this->user->id,
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
