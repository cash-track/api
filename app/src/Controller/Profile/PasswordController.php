<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Request\Profile\UpdatePasswordRequest;
use App\Service\Auth\AuthService;
use App\Service\UserService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class PasswordController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        protected ResponseWrapper $response,
        protected AuthService $authService,
        protected UserService $userService,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/profile/password', name: 'profile.update.password', methods: 'PUT', group: 'auth')]
    public function updatePassword(UpdatePasswordRequest $request): ResponseInterface
    {
        $this->authService->hashPassword($this->user, $request->newPassword);

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
