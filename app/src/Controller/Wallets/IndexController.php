<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Charge;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\WalletRepository;
use App\View\WalletView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class IndexController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private WalletRepository $walletRepository,
        private WalletView $walletView,
        private ChargeRepository $chargeRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id:\d+>', name: 'wallet.index', methods: 'GET', group: 'auth')]
    public function index(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->walletView->json($wallet);
    }

    #[Route(route: '/wallets/<id>/total', name: 'wallet.index.total', methods: 'GET', group: 'auth')]
    public function indexTotal(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->response->json([
            'data' => [
                'totalAmount' => $wallet->totalAmount,
                'totalIncomeAmount' => $this->chargeRepository->totalByWalletPK($id, Charge::TYPE_INCOME),
                'totalExpenseAmount' => $this->chargeRepository->totalByWalletPK($id, Charge::TYPE_EXPENSE),
            ],
        ]);
    }
}
