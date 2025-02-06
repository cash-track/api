<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LoginRequest;
use App\Service\Auth\AuthService;
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
        protected ResponseWrapper $response,
        protected readonly AuthService $authService,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/login', name: 'auth.login', methods: 'POST')]
    public function login(LoginRequest $request): ResponseInterface
    {
        try {
            $auth = $this->authService->login($request->email, $request->password);
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException($exception->getMessage());
        }

        if ($auth === null) {
            return $this->responseAuthenticationFailure();
        }

        return $this->responseTokensWithUser($auth);
    }
}
