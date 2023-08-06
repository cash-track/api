<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\CheckNickNameRequest;
use App\Request\RegisterRequest;
use App\Service\Auth\AuthService;
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
        protected ResponseWrapper $response,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/register', name: 'auth.register', methods: 'POST')]
    public function register(RegisterRequest $request): ResponseInterface
    {
        $user = $request->createUser();

        try {
            $auth = $this->authService->register($user, $request->locale);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('user_register_exception'),
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->responseTokensWithUser($auth);
    }

    #[Route(route: '/auth/register/check/nick-name', name: 'auth.register.check.nickname', methods: 'POST')]
    public function checkNickName(CheckNickNameRequest $_): ResponseInterface
    {
        return $this->response->json([
            'message' => $this->say('nick_name_register_free'),
        ]);
    }
}
