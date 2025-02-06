<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\LoginPasskeyRequest;
use App\Service\Auth\AuthService;
use App\Service\Auth\Passkey\Exception\InvalidChallengeException;
use App\Service\Auth\Passkey\Exception\InvalidClientResponseException;
use App\Service\Auth\Passkey\Exception\PasskeyNotFoundException;
use App\Service\Auth\Passkey\Exception\UserNotFoundException;
use App\Service\Auth\Passkey\PasskeyService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;
use Webauthn\Exception\WebauthnException;

final class PasskeyController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        protected readonly AuthService $authService,
        protected readonly PasskeyService $passkeyService,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/login/passkey/init', name: 'auth.login.passkey.init', methods: 'GET')]
    public function init(): ResponseInterface
    {
        try {
            $response = $this->passkeyService->initAuth();
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException(
                error: $exception->getMessage(),
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
            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_invalid_challenge'),
            );
        } catch (InvalidClientResponseException $exception) {
            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_invalid_response'),
            );
        } catch (PasskeyNotFoundException | UserNotFoundException $exception) {
            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_auth_passkey_unregistered'),
            );
        } catch (WebauthnException $exception) {
            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_authentication_passkey'),
            );
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException($exception->getMessage());
        }

        try {
            $auth = $this->authService->authenticate($user);
        } catch (\Throwable $exception) {
            return $this->responseAuthenticationException($exception->getMessage());
        }

        return $this->responseTokensWithUser($auth);
    }
}
