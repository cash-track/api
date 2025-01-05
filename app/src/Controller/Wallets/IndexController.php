<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use App\Repository\WalletRepository;
use App\View\WalletView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class IndexController extends Controller
{
    public function __construct(
        AuthContextInterface $auth,
        private ResponseWrapper $response,
        private WalletRepository $walletRepository,
        private WalletView $walletView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>', name: 'wallet.index', methods: 'GET', group: 'auth')]
    public function index(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->walletView->json($wallet);
    }
}
