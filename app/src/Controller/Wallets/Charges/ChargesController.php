<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Charges;

use App\Controller\Wallets\Controller;
use App\Database\Charge;
use App\Database\Wallet;
use App\Request\Charge\CreateRequest;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

class ChargesController extends Controller
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets/<walletId>/charges", name="wallet.charge.list", methods="GET", group="auth")
     *
     * @param int $walletId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function list(int $walletId): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($walletId, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charges = $this->charges->findByWalletId($wallet->id);

        if (!is_array($charges) || count($charges) === 0) {
            return $this->response->json(['data' => []]);
        }

        return $this->chargesView->json($charges);
    }

    /**
     * @Route(route="/wallets/<walletId>/charges", name="wallet.charge.create", methods="POST", group="auth")
     *
     * @param int $walletId
     * @param \App\Request\Charge\CreateRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function create(int $walletId, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->wallets->findByPKByUserPK($walletId, $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        if ( ! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $charge = new Charge();
        $charge->type = $request->getType();
        $charge->amount = $request->getAmount();
        $charge->title = $request->getTitle();
        $charge->description = $request->getDescription();
        $charge->wallet = $wallet;
        $charge->walletId = $wallet->id;
        $charge->user = $this->user;
        $charge->userId = $this->user->id;

        // TODO. Implement currency conversion when charge currency is different that wallet.

        // TODO. Update wallet total.

        try {
            $this->chargeService->store($charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store charge', [
                'action' => 'wallet.charge.create',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to create charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->json($charge);
    }
}