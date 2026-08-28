<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LoginRequest;
use App\Service\Auth\Authentication;
use App\Service\Auth\AuthService;
use App\Service\Auth\LoginBackoffService;
use App\Service\Auth\LoginThrottledException;
use App\Service\Metrics\AppMetricsInterface;
use App\View\UserView;
use OpenTelemetry\API\Trace\StatusCode;
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

    private const string METHOD = 'password';

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        private readonly LoginBackoffService $loginBackoff,
        private readonly AppMetricsInterface $metrics,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/login', name: 'auth.login', methods: 'POST')]
    public function login(LoginRequest $request, TracerInterface $tracer): ResponseInterface
    {
        try {
            $this->loginBackoff->assertNotThrottled($request->email);
        } catch (LoginThrottledException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseLoginThrottled($exception->getRetryAfter());
        }

        try {
            $auth = $tracer->trace(
                name: 'auth.login',
                callback: static function (SpanInterface $span, AuthService $authService) use ($request): ?Authentication {
                    $auth = $authService->login($request->email, $request->password);

                    $span->setAttributes([
                        'result' => $auth !== null,
                        'user.id' => $auth?->user?->id
                    ]);
                    $span->setStatus(
                        $auth !== null ? StatusCode::STATUS_OK : StatusCode::STATUS_ERROR
                    );

                    return $auth;
                },
                scoped: true,
                traceKind: TraceKind::CLIENT,
            );
        } catch (\Throwable $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationException($exception->getMessage());
        }

        if ($auth === null) {
            $this->loginBackoff->recordFailure($request->email);
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure();
        }

        $this->loginBackoff->recordSuccess($request->email);
        $this->metrics->incrementLogin(self::METHOD, true);

        return $this->responseTokensWithUser($auth);
    }
}
