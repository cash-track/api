<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Exception\ClientErrorException;
use App\Service\Auth\EmailConfirmationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

/**
 * Public counterpart of {@see EmailConfirmationsController}. The confirmation link is opened
 * from an email, so this action must stay off AuthAwareController.
 */
final class ConfirmEmailController
{
    use TranslatorTrait;

    public function __construct(
        protected readonly ResponseWrapper $response,
        protected readonly EmailConfirmationService $emailConfirmationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(route: '/auth/email/confirmation/confirm/<token>', name: 'auth.email.confirm')]
    public function confirm(string $token): ResponseInterface
    {
        try {
            $this->emailConfirmationService->confirm($token);
        } catch (ClientErrorException $exception) {
            return $this->response->json([
                'message' => $this->say('email_confirmation_confirm_failure'),
                'error' => $exception->getMessage(),
            ], 400);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to confirm email', ['exception' => $exception]);

            return $this->response->json([
                'message' => $this->say('email_confirmation_confirm_failure'),
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->response->json([
            'message' => $this->say('email_confirmation_ok'),
        ]);
    }
}
