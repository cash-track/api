<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Database\EmailConfirmation;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class EmailConfirmationsController
{
    use PrototypeTrait;

    /**
     * @Route(route="/auth/email/confirmation", name="auth.email.confirmation", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $confirmation = $this->emailConfirmations->findByPK($user->email);

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
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        try {
            $this->emailConfirmationService->reSend($user);
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
