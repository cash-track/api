<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use App\Request\Wallet\CreateRequest;
use App\Request\Wallet\UpdateRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class WalletsController extends Controller
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets", name="wallet.list", methods="GET", group="auth")
     *
     * @return string
     */
    public function list(): array
    {
        return [
            'data' => $this->user->wallets->map(function ($wallet) {
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
        $wallet = $this->wallets->findByPKByUserPK($id, $this->user->id);

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
        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $wallet = $this->walletService->create($request->createWallet(), $this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to create new wallet. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->walletView->json($wallet);
    }

    /**
     * @Route(route="/wallets/<id>", name="wallet.update", methods="PUT", group="auth")
     *
     * @param int $id
     * @param \App\Request\Wallet\UpdateRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(int $id, UpdateRequest $request): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $wallet->name = $request->getName();
        $wallet->isPublic = $request->getIsPublic();
        $wallet->defaultCurrencyCode = $request->getDefaultCurrencyCode();

        try {
            $wallet->defaultCurrency = $this->currencies->findByPK($request->getDefaultCurrencyCode());
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load currency entity', [
                'action' => 'wallet.update',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);
        }

        try {
            $this->walletService->store($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store wallet', [
                'action' => 'wallet.update',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->walletView->json($wallet);
    }

    /**
     * @Route(route="/wallets/<id>", name="wallet.delete", methods="DELETE", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete(int $id): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->delete($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete wallet', [
                'action' => 'wallet.delete',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to delete wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
