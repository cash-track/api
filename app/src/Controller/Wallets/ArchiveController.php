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

final class ArchiveController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        AuthContextInterface $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private WalletService $walletService,
        private WalletRepository $walletRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/archive', name: 'wallet.archive', methods: 'POST', group: 'auth')]
    public function archive(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->archive($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to archive wallet', [
                'action' => 'wallet.archive',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('wallet_archive_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }

    #[Route(route: '/wallets/<id>/un-archive', name: 'wallet.unarchive', methods: 'POST', group: 'auth')]
    public function unArchive(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->unArchive($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to un-archive wallet', [
                'action' => 'wallet.unarchive',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('wallet_un_archive_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
