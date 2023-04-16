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
use Spiral\Translator\Traits\TranslatorTrait;

final class RegisterController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected AuthService $authService,
        protected UserService $userService,
        protected ResponseWrapper $response,
        protected EmailConfirmationService $emailConfirmationService,
        protected RefreshTokenService $refreshTokenService,
        private CurrencyRepository $currencyRepository,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/register', name: 'auth.register', methods: 'POST')]
    public function register(RegisterRequest $request): ResponseInterface
    {
        $user = $request->createUser();

        $currency = $this->currencyRepository->getDefault();

        if ($currency instanceof Currency) {
            $user->setDefaultCurrency($currency);
        }

        $this->authService->hashPassword($user, $request->password);

        try {
            $user = $this->userService->store($user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('user_register_exception'),
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
            'message' => $this->say('nick_name_register_free'),
        ]);
    }
}
