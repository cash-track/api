<?php

declare(strict_types = 1);

namespace App\Controller\Profile;

use App\Request\Profile\UpdateBasicRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class ProfileController
{
    use PrototypeTrait;

    /**
     * @Route(route="/profile", name="profile.index", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        return $this->userView->json($user);
    }

    /**
     * @Route(route="/profile", name="profile.update", methods="PUT", group="auth")
     *
     * @param \App\Request\Profile\UpdateBasicRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(UpdateBasicRequest $request): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $request->setContext($user);

        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $user->name = $request->getName();
        $user->lastName = $request->getLastName();
        $user->nickName = $request->getNickName();
        $user->defaultCurrencyCode = $request->getDefaultCurrencyCode();

        try {
            $user->defaultCurrency = $this->currencies->findByPK($request->getDefaultCurrencyCode());
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load currency entity', [
                'action' => 'profile.update',
                'id'     => $user->id,
                'msg'    => $exception->getMessage(),
            ]);
        }

        try {
            $this->userService->store($user);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store user', [
                'action' => 'profile.update',
                'id'     => $user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update basic user profile. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->userView->json($user);
    }
}
