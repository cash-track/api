<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Wallet;
use App\Request\Wallet\CreateRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class WalletsController
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets", name="wallet.list", methods="GET", group="auth")
     *
     * @return string
     */
    public function list(): array
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        return [
            'data' => $user->wallets->map(function ($wallet) {
                return $this->walletView->map($wallet);
            })->getValues(),
        ];
    }

    /**
     * @Route(route="/wallets/<id>", name="wallet.index", methods="GET", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(int $id): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $wallet = $this->wallets->findByPKByUserPK($id, $user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->walletView->json($wallet);
    }

    /**
     * @Route(route="/wallets", name="wallet.create", methods="POST", group="auth")
     *
     * @param \App\Request\Wallet\CreateRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create(CreateRequest $request): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        $request->setContext($user);

        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $wallet = $this->walletService->create($request->createWallet(), $user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to create new wallet. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->walletView->json($wallet);
    }
}
