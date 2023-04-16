<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\User;
use App\Repository\UserRepository;
use App\Request\LoginRequest;
use App\Service\Auth\AuthService;
use App\Service\Auth\RefreshTokenService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class LoginController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected AuthService $authService,
        protected ResponseWrapper $response,
        protected UserRepository $userRepository,
        protected RefreshTokenService $refreshTokenService,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/login', name: 'auth.login', methods: 'POST')]
    public function login(LoginRequest $request): ResponseInterface
    {
        try {
            $user = $this->userRepository->findByEmail($request->email);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'error' => $exception->getMessage(),
                'message' => $this->say('error_authentication_exception'),
            ], 500);
        }

        if (! $user instanceof User) {
            return $this->responseAuthenticationFailure();
        }

        if (! $this->authService->verifyPassword($user, $request->password)) {
            return $this->responseAuthenticationFailure();
        }

        $accessToken = $this->authService->authenticate($user);
        $refreshToken = $this->refreshTokenService->authenticate($user);

        return $this->responseTokensWithUser($accessToken, $refreshToken, $user);
    }
}
