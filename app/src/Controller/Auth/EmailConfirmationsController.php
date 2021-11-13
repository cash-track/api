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

final class EmailConfirmationsController extends AuthAwareController
{
    /**
     * @param \Spiral\Auth\AuthScope $auth
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\View\EmailConfirmationView $emailConfirmationView
     * @param \App\Service\Auth\EmailConfirmationService $emailConfirmationService
     * @param \App\Repository\EmailConfirmationRepository $emailConfirmationRepository
     */
    public function __construct(
        protected AuthScope $auth,
        protected ResponseWrapper $response,
        protected EmailConfirmationView $emailConfirmationView,
        protected EmailConfirmationService $emailConfirmationService,
        protected EmailConfirmationRepository $emailConfirmationRepository,
    ) {
        parent::__construct($auth);
    }

    /**
     * @Route(route="/auth/email/confirmation", name="auth.email.confirmation", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        $confirmation = $this->emailConfirmationRepository->findByPK($this->user->email);

        if (! $confirmation instanceof EmailConfirmation) {
            return $this->response->json([
                'data' => null,
            ]);
        }

        return $this->emailConfirmationView->json($confirmation);
    }

    /**
     * @Route(route="/auth/email/confirmation/<token>", name="auth.email.confirm")
     *
     * @param string $token
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function confirm(string $token): ResponseInterface
    {
        try {
            $this->emailConfirmationService->confirm($token);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to confirm your email',
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => 'Your email has been confirmed',
        ]);
    }

    /**
     * @Route(route="/auth/email/confirmation/resend", name="auth.email.resend", methods="POST", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reSend(): ResponseInterface
    {
        try {
            $this->emailConfirmationService->reSend($this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Error on trying to send new confirmation link.',
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => 'Confirmation message has been sent.',
        ]);
    }
}
