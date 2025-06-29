<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Charge;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\WalletRepository;
use App\Service\ChargeWalletService;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class TotalController extends Controller
{
    public function __construct(
        AuthContextInterface $auth,
        private readonly ResponseWrapper $response,
        private readonly WalletRepository $walletRepository,
        private readonly ChargeRepository $chargeRepository,
        private readonly ChargeWalletService $chargeWalletService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/total', name: 'wallet.index.total', methods: 'GET', group: 'auth')]
    public function total(string $id, InputManager $input): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $this->chargeRepository->filter($input->query->fetch(['date-from', 'date-to']));

        $income = $this->chargeRepository->totalByWalletPK((int) $wallet->id, Charge::TYPE_INCOME);
        $expense = $this->chargeRepository->totalByWalletPK((int) $wallet->id, Charge::TYPE_EXPENSE);

        $response = [
            'totalAmount' => $this->chargeWalletService->totalByIncomeAndExpense($income, $expense),
            'totalIncomeAmount' => $income,
            'totalExpenseAmount' => $expense,
        ];

        $this->totalSafeCheckByInput($wallet, $input, $income, $expense);

        $tagIDs = $this->fetchFilteredTagIDs($input);

        if (count($tagIDs) > 0) {
            $incomePerTag = $this->chargeRepository->totalByWalletPKGroupByTagPKs((int) $wallet->id, $tagIDs, Charge::TYPE_INCOME);
            $expensePerTag = $this->chargeRepository->totalByWalletPKGroupByTagPKs((int) $wallet->id, $tagIDs, Charge::TYPE_EXPENSE);
            $response['tags'] = $this->mergeGroupedTotal($tagIDs, $incomePerTag, $expensePerTag);
        }

        return $this->response->json([
            'data' => $response,
        ]);
    }

    private function mergeGroupedTotal(array $tagIDs, array $income, array $expense): array
    {
        $data = [];

        foreach ($tagIDs as $id) {
            if (!array_key_exists($id, $income) && !array_key_exists($id, $expense)) {
                continue;
            }

            $data[] = [
                'tagId' => $id,
                'totalIncomeAmount' => $income[$id] ?? 0.0,
                'totalExpenseAmount' => $expense[$id] ?? 0.0,
            ];
        }

        return $data;
    }

    private function totalSafeCheckByInput(Wallet $wallet, InputManager $input, float $income, float $expense): void
    {
        if ($input->query->has('date-from') || $input->query->has('date-to') || $input->query->has('tags')) {
            return;
        }

        $this->chargeWalletService->totalSafeCheck($wallet, $income, $expense);
    }
}
