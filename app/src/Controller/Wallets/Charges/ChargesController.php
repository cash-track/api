<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Charges;

use App\Controller\Wallets\Controller;
use App\Database\Charge;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\Request\Charge\CreateRequest;
use App\Service\ChargeWalletService;
use App\Service\Pagination\PaginationFactory;
use App\View\ChargesView;
use App\View\ChargeView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

class ChargesController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private PaginationFactory $paginationFactory,
        private ChargesView $chargesView,
        private ChargeView $chargeView,
        private ChargeWalletService $chargeWalletService,
        private ChargeRepository $chargeRepository,
        private WalletRepository $walletRepository,
        private TagRepository $tagRepository,
    ) {
        parent::__construct($auth);
    }

    /**
     * @Route(route="/wallets/<walletId>/charges", name="wallet.charge.list", methods="GET", group="auth")
     *
     * @param int $walletId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function list(int $walletId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository
            ->paginate($this->paginationFactory->createPaginator())
            ->findByWalletIdWithPagination((int) $wallet->id);

        return $this->chargesView->jsonPaginated($charges, $this->chargeRepository->getPaginationState());
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
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $charge = new Charge();
        $charge->type = $request->getType();
        $charge->amount = $request->getAmount();
        $charge->title = $request->getTitle();
        $charge->description = $request->getDescription();
        $charge->setWallet($wallet);
        $charge->setUser($this->user);

        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->getTagIDs(), $wallet->getUserIDs());

        foreach ($tags as $tag) {
            $charge->tags->add($tag);
        }

        // TODO. Implement currency conversion when charge currency is different that wallet.

        try {
            $this->chargeWalletService->create($wallet, $charge);
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

    /**
     * @Route(route="/wallets/<walletId>/charges/<chargeId>", name="wallet.charge.update", methods="PUT", group="auth")
     *
     * @param int $walletId
     * @param string $chargeId
     * @param \App\Request\Charge\CreateRequest $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(int $walletId, string $chargeId, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = $this->chargeRepository->findByPKByWalletPK($chargeId, $walletId);

        if (! $charge instanceof Charge) {
            return $this->response->create(404);
        }

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $oldCharge = clone $charge;

        $charge->type = $request->getType();
        $charge->amount = $request->getAmount();
        $charge->title = $request->getTitle();
        $charge->description = $request->getDescription();

        // TODO. Implement currency conversion when charge currency is different that wallet.

        try {
            $this->chargeWalletService->update($wallet, $oldCharge, $charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store charge', [
                'action' => 'wallet.charge.update',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->json($charge);
    }

    /**
     * @Route(route="/wallets/<walletId>/charges/<chargeId>", name="wallet.charge.delete", methods="DELETE", group="auth")
     *
     * @param int $walletId
     * @param string $chargeId
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function delete(int $walletId, string $chargeId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = $this->chargeRepository->findByPKByWalletPK($chargeId, $walletId);

        if (! $charge instanceof Charge) {
            return $this->response->create(404);
        }

        try {
            $this->chargeWalletService->delete($wallet, $charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete charge', [
                'action' => 'wallet.charge.delete',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to delete charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
