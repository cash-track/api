<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Service\Auth\Exception\InvalidTokenException;
use App\Service\Auth\GoogleAuthService;
use App\Service\Metrics\AppMetricsInterface;
use App\View\UserView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class ProviderController extends Controller
{
    use TranslatorTrait;

    private const string METHOD = 'google';

    public function __construct(
        protected UserView $userView,
        protected ResponseWrapper $response,
        protected readonly GoogleAuthService $googleAuthService,
        private readonly AppMetricsInterface $metrics,
    ) {
        parent::__construct($userView, $response);
    }

    #[Route(route: '/auth/provider/google', name: 'auth.provider.google', methods: 'POST')]
    public function google(InputManager $input): ResponseInterface
    {
        try {
            $auth = $this->googleAuthService->loginOrRegister($input->post('token', ''));
        } catch (InvalidTokenException $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationFailure(
                error: $exception->getMessage(),
                message: $this->say('error_token_authentication_failure'),
            );
        } catch (\Throwable $exception) {
            $this->metrics->incrementLogin(self::METHOD, false);

            return $this->responseAuthenticationException($exception->getMessage());
        }

        $this->metrics->incrementLogin(self::METHOD, true);

        return $this->responseTokensWithUser($auth);
    }
}
