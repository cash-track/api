<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use App\Repository\WalletRepository;
use App\Service\WalletService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class ActiveController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        AuthContextInterface $auth,
        private readonly ResponseWrapper $response,
        private readonly LoggerInterface $logger,
        private readonly WalletService $walletService,
        private readonly WalletRepository $walletRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/activate', name: 'wallet.activate', methods: 'POST', group: 'auth')]
    public function activate(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

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
                'message' => $this->say('wallet_activate_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }

    #[Route(route: '/wallets/<id>/disable', name: 'wallet.disable', methods: 'POST', group: 'auth')]
    public function disable(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

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
                'message' => $this->say('wallet_disable_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
