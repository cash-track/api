<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Database\Charge;
use App\Database\Tag;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Service\ChargeWalletService;
use App\Service\Pagination\PaginationFactory;
use App\View\ChargesView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class ChargesController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private TagRepository $tagRepository,
        private ChargeRepository $chargeRepository,
        private PaginationFactory $paginationFactory,
        private ChargesView $chargesView,
        private ChargeWalletService $chargeWalletService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags/<id:\d+>/charges', name: 'tag.charges', methods: 'GET', group: 'auth')]
    public function list(int $id): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK($id, (int) $this->user->id);
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository
            ->paginate($this->paginationFactory->createPaginator())
            ->findByTagIdWithPagination((int) $tag->id);

        return $this->chargesView->jsonPaginated($charges, $this->chargeRepository->getPaginationState());
    }

    #[Route(route: '/tags/<id:\d+>/charges/total', name: 'tag.charges.total', methods: 'GET', group: 'auth')]
    public function total(int $id): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUserPK($id, (int) $this->user->id);
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $income = $this->chargeRepository->totalByTagPK((int) $tag->id, Charge::TYPE_INCOME);
        $expense = $this->chargeRepository->totalByTagPK((int) $tag->id, Charge::TYPE_EXPENSE);

        return $this->response->json([
            'data' => [
                'totalAmount' => $this->chargeWalletService->totalByIncomeAndExpense($income, $expense),
                'totalIncomeAmount' => $income,
                'totalExpenseAmount' => $expense,
            ],
        ]);
    }
}
