<?php

declare(strict_types=1);

namespace App\Controller\Profile;

use App\Controller\AuthAwareController;
use App\Request\Profile\UpdatePasswordRequest;
use App\Service\Auth\AuthService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class PasswordController extends AuthAwareController
{
    use TranslatorTrait;

    public function __construct(
        AuthContextInterface $auth,
        protected ResponseWrapper $response,
        protected AuthService $authService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/profile/password', name: 'profile.update.password', methods: 'PUT', group: 'auth')]
    public function updatePassword(UpdatePasswordRequest $request): ResponseInterface
    {
        try {
            $this->authService->updatePassword($this->user, $request->newPassword);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('password_change_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json([
            'message' => $this->say('password_change_ok'),
        ], 200);
    }
}
