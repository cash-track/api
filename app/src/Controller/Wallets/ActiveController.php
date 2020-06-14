<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class ActiveController extends Controller
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets/<id>/activate", name="wallet.activate", methods="POST", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function activate(int $id): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->activate($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to activate wallet', [
                'action' => 'wallet.activate',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to activate wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }

    /**
     * @Route(route="/wallets/<id>/disable", name="wallet.disable", methods="POST", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function disable(int $id): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($id, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->disable($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to disable wallet', [
                'action' => 'wallet.disable',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to disable wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
