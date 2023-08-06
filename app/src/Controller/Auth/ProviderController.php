<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\Exception\InvalidTokenException;
use App\Service\Auth\GoogleAuthService;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class ProviderController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        protected GoogleAuthService $googleAuthService,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/provider/google', name: 'auth.provider.google', methods: 'POST')]
    public function google(InputManager $input): ResponseInterface
    {
        try {
            $auth = $this->googleAuthService->loginOrRegister($input->post('token', ''));
        } catch (InvalidTokenException $exception) {
            return $this->responseAuthenticationFailure(
                $exception->getMessage(),
                $this->say('error_token_authentication_failure'),
            );
        } catch (\Throwable $exception) {
            return $this->response->json([
                'error' => $exception->getMessage(),
                'message' => $this->say('error_authentication_exception'),
            ], 500);
        }

        return $this->responseTokensWithUser($auth);
    }
}
