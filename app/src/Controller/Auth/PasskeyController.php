<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LoginPasskeyRequest;
use App\Service\Auth\AuthService;
use App\Service\Auth\Passkey\Exception\InvalidChallengeException;
use App\Service\Auth\Passkey\Exception\InvalidClientResponseException;
use App\Service\Auth\Passkey\Exception\PasskeyNotFoundException;
use App\Service\Auth\Passkey\Exception\PasskeyServiceUnavailableException;
use App\Service\Auth\Passkey\Exception\UserNotFoundException;
use App\Service\Auth\Passkey\PasskeyService;
use App\Service\Metrics\AppMetricsInterface;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;
use Webauthn\Exception\WebauthnException;

final class PasskeyController extends Controller
{
    use TranslatorTrait;

    private const string METHOD = 'passkey';

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        LoggerInterface $logger,
        protected readonly AuthService $authService,
        protected readonly PasskeyService $passkeyService,
        private readonly AppMetricsInterface $metrics,
    ) {
        parent::__construct($userView, $response, $logger);
    }

    #[Route(route: '/auth/login/passkey/init', name: 'auth.login.passkey.init', methods: 'GET')]
    public function init(): ResponseInterface
    {
        try {
            $response = $this->passkeyService->initAuth();
        } catch (PasskeyServiceUnavailableException $exception) {
            return $this->responseServiceUnavailable(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_unavailable'),
            );
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException(
                exception: $exception,
                message: $this->say('passkey_init_exception'),
            );
        }

        return $this->response->json($response);
    }

    #[Route(route: '/auth/login/passkey', name: 'auth.login.passkey', methods: 'POST')]
    public function login(LoginPasskeyRequest $request): ResponseInterface
    {
        try {
            $user = $this->passkeyService->authenticate($request->challenge, $request->data);
        } catch (InvalidChallengeException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_invalid_challenge'),
            );
        } catch (InvalidClientResponseException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_invalid_response'),
            );
        } catch (PasskeyNotFoundException | UserNotFoundException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_unregistered'),
            );
        } catch (WebauthnException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_authentication_passkey'),
            );
        } catch (PasskeyServiceUnavailableException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseServiceUnavailable(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_unavailable'),
            );
        } catch (\Throwable $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationException($exception);
        }

        try {
            $auth = $this->authService->authenticate($user);
        } catch (\Throwable $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationException($exception);
        }

        $this->metrics->incrementLogin(self::METHOD, true);

        return $this->responseTokensWithUser($auth);
    }
}
