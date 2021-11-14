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
    /**
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\Service\Auth\ForgotPasswordService $forgotPasswordService
     */
    public function __construct(
        protected ResponseWrapper $response,
        protected ForgotPasswordService $forgotPasswordService,
    ) {
    }

    /**
     * @Route(route="/auth/password/forgot", name="auth.password.forgot", methods="POST")
     *
     * @param \App\Request\ForgotPasswordCreateRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create(ForgotPasswordCreateRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $this->forgotPasswordService->create($request->getEmail());
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

    /**
     * @Route(route="/auth/password/reset", name="auth.password.reset", methods="POST")
     *
     * @param \App\Request\ForgotPasswordResetRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function reset(ForgotPasswordResetRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $this->forgotPasswordService->reset($request->getCode(), $request->getPassword());
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
