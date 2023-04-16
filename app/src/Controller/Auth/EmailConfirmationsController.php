<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Controller\AuthAwareController;
use App\Database\EmailConfirmation;
use App\Repository\EmailConfirmationRepository;
use App\Service\Auth\EmailConfirmationService;
use App\View\EmailConfirmationView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class EmailConfirmationsController extends AuthAwareController
{
    use TranslatorTrait;

    public function __construct(
        protected AuthScope $auth,
        protected ResponseWrapper $response,
        protected EmailConfirmationView $emailConfirmationView,
        protected EmailConfirmationService $emailConfirmationService,
        protected EmailConfirmationRepository $emailConfirmationRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/auth/email/confirmation', name: 'auth.email.confirmation', methods: 'GET', group: 'auth')]
    public function index(): ResponseInterface
    {
        /** @var \App\Database\EmailConfirmation|null $confirmation */
        $confirmation = $this->emailConfirmationRepository->findByPK($this->user->email);

        if (! $confirmation instanceof EmailConfirmation) {
            return $this->response->json([
                'data' => null,
            ]);
        }

        return $this->emailConfirmationView->json($confirmation);
    }

    #[Route(route: '/auth/email/confirmation/confirm/<token>', name: 'auth.email.confirm')]
    public function confirm(string $token): ResponseInterface
    {
        try {
            $this->emailConfirmationService->confirm($token);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('email_confirmation_confirm_failure'),
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => $this->say('email_confirmation_ok'),
        ]);
    }

    #[Route(route: '/auth/email/confirmation/resend', name: 'auth.email.resend', methods: 'POST', group: 'auth')]
    public function reSend(): ResponseInterface
    {
        try {
            $this->emailConfirmationService->reSend($this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => $this->say('email_confirmation_resend_failure'),
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => $this->say('email_confirmation_resend_ok'),
        ]);
    }
}
