<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Currency;
use App\Database\Wallet;
use App\Repository\CurrencyRepository;
use App\Repository\WalletRepository;
use App\Request\Wallet\CreateRequest;
use App\Request\Wallet\UpdateRequest;
use App\Service\WalletService;
use App\View\WalletView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class WalletsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private WalletRepository $walletRepository,
        private WalletService $walletService,
        private WalletView $walletView,
        private CurrencyRepository $currencyRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets', name: 'wallet.create', methods: 'POST', group: 'auth')]
    public function create(CreateRequest $request): ResponseInterface
    {
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

    #[Route(route: '/wallets/<id>', name: 'wallet.update', methods: 'PUT', group: 'auth')]
    public function update(string $id, UpdateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $wallet->name = $request->name;
        $wallet->isPublic = $request->isPublic;
        $wallet->defaultCurrencyCode = $request->defaultCurrencyCode;

        try {
            /** @var \App\Database\Currency|null $defaultCurrency */
            $defaultCurrency = $this->currencyRepository->findByPK($request->defaultCurrencyCode);

            if (! $defaultCurrency instanceof Currency) {
                throw new \RuntimeException('Unable to load default currency');
            }

            $wallet->setDefaultCurrency($defaultCurrency);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load currency entity', [
                'action' => 'wallet.update',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
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

    #[Route(route: '/wallets/<id>', name: 'wallet.delete', methods: 'DELETE', group: 'auth')]
    public function delete(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

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
