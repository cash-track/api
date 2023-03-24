<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Request\ForgotPasswordCreateRequest;
use App\Request\ForgotPasswordResetRequest;
use App\Service\Auth\ForgotPasswordService;
use App\Service\Auth\ForgotPasswordThrottledException;
use Psr\Http\Message\ResponseInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class ForgotPasswordController
{
    public function __construct(
        protected readonly ResponseWrapper $response,
        protected readonly ForgotPasswordService $forgotPasswordService,
    ) {
    }

    #[Route(route: '/auth/password/forgot', name: 'auth.password.forgot', methods: 'POST')]
    public function create(ForgotPasswordCreateRequest $request): ResponseInterface
    {
        try {
            $this->forgotPasswordService->create($request->email);
        } catch (ForgotPasswordThrottledException $exception) {
            return $this->response->json([
                'message' => $exception->getMessage(),
            ], 400);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to reset your password.',
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => 'Email with reset password link has been sent.'
        ], 200);
    }

    #[Route(route: '/auth/password/reset', name: 'auth.password.reset', methods: 'POST')]
    public function reset(ForgotPasswordResetRequest $request): ResponseInterface
    {
        try {
            $this->forgotPasswordService->reset($request->code, $request->password);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to reset your password.',
                'error' => $exception->getMessage(),
            ], 400);
        }

        return $this->response->json([
            'message' => 'Your password has been changed.'
        ], 200);
    }
}
