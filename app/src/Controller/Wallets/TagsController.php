<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Charge;
use App\Database\Tag;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\Service\ChargeWalletService;
use App\View\TagsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class TagsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private WalletRepository $walletRepository,
        private ChargeRepository $chargeRepository,
        private TagRepository $tagRepository,
        private TagsView $tagsView,
        private ChargeWalletService $chargeWalletService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id:\d+>/tags', name: 'wallet.tags.list', methods: 'GET', group: 'auth')]
    public function list(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tags = $this->tagRepository->findAllByWalletPK((int) $wallet->id);

        return $this->tagsView->json($tags);
    }

    #[Route(route: '/wallets/<walletId:\d+>/tags/<tagId:\d+>/total', name: 'wallet.tags.total', methods: 'GET', group: 'auth')]
    public function total(int $walletId, int $tagId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tag = $this->tagRepository->findByPKByUsersPK($tagId, $wallet->getUserIDs());

        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $income = $this->chargeRepository->totalByWalletPKAndTagId($walletId, $tagId, Charge::TYPE_INCOME);
        $expense = $this->chargeRepository->totalByWalletPKAndTagId($walletId, $tagId, Charge::TYPE_EXPENSE);

        return $this->response->json([
            'data' => [
                'totalAmount' => $this->chargeWalletService->totalByIncomeAndExpense($income, $expense),
                'totalIncomeAmount' => $income,
                'totalExpenseAmount' => $expense,
            ],
        ]);
    }

    #[Route(route: '/wallets/<walletId:\d+>/tags/find/<query>', name: 'wallet.tags.find', methods: 'GET', group: 'auth')]
    public function find(int $walletId, string $query = ''): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tags = $this->tagRepository->searchAllByUsersPK($wallet->getUserIDs(), urldecode($query));

        return $this->tagsView->json($tags);
    }
}
