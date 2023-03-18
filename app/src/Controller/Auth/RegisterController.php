<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\Currency;
use App\Repository\CurrencyRepository;
use App\Request\CheckNickNameRequest;
use App\Request\RegisterRequest;
use App\Service\Auth\AuthService;
use App\Service\Auth\EmailConfirmationService;
use App\Service\Auth\RefreshTokenService;
use App\Service\UserService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class RegisterController
{
    use AuthResponses;

    /**
     * @param \App\View\UserView $userView
     * @param \App\Service\Auth\AuthService $authService
     * @param \App\Service\UserService $userService
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\Service\Auth\EmailConfirmationService $emailConfirmationService
     * @param \App\Service\Auth\RefreshTokenService $refreshTokenService
     */
    public function __construct(
        protected UserView $userView,
        protected AuthService $authService,
        protected UserService $userService,
        protected ResponseWrapper $response,
        protected EmailConfirmationService $emailConfirmationService,
        protected RefreshTokenService $refreshTokenService,
        private CurrencyRepository $currencyRepository,
    ) {
    }

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

        $currency = $this->currencyRepository->getDefault();

        if ($currency instanceof Currency) {
            $user->setDefaultCurrency($currency);
        }

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

    #[Route(route: '/auth/register/check/nick-name', name: 'auth.register.check.nickname', methods: 'POST')]
    public function checkNickName(CheckNickNameRequest $_): ResponseInterface
    {
        return $this->response->json([
            'message' => 'Nick name are free to register'
        ]);
    }
}
