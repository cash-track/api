<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LoginRequest;
use App\Service\Auth\Authentication;
use App\Service\Auth\AuthService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Telemetry\SpanInterface;
use Spiral\Telemetry\TraceKind;
use Spiral\Telemetry\TracerInterface;
use Spiral\Translator\Traits\TranslatorTrait;

final class LoginController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/login', name: 'auth.login', methods: 'POST')]
    public function login(LoginRequest $request, TracerInterface $tracer): ResponseInterface
    {
        try {
            $auth = $tracer->trace(
                name: 'auth.login',
                callback: static function (SpanInterface $span, AuthService $authService) use ($request): ?Authentication {
                    $auth = $authService->login($request->email, $request->password);

                    $span->setAttributes([
                        'result' => $auth !== null,
                        'user.id' => $auth?->user?->id
                    ]);

                    return $auth;
                },
                scoped: true,
                traceKind: TraceKind::CLIENT,
            );
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException($exception->getMessage());
        }

        if ($auth === null) {
            return $this->responseAuthenticationFailure();
        }

        return $this->responseTokensWithUser($auth);
    }
}
