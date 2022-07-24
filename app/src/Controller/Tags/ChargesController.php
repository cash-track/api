<?php

declare(strict_types=1);

namespace App\Controller\Tags;

use App\Controller\AuthAwareController;
use App\Database\Charge;
use App\Database\Tag;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\ChargeWalletService;
use App\Service\Pagination\PaginationFactory;
use App\Service\Statistics\ChargeAmountGraph;
use App\View\ChargesView;
use App\View\CurrencyView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class ChargesController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        private readonly ResponseWrapper $response,
        private readonly TagRepository $tagRepository,
        private readonly ChargeRepository $chargeRepository,
        private readonly PaginationFactory $paginationFactory,
        private readonly ChargesView $chargesView,
        private readonly ChargeWalletService $chargeWalletService,
        private readonly UserRepository $userRepository,
        private readonly CurrencyView $currencyView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/tags/<id:\d+>/charges', name: 'tag.charges', methods: 'GET', group: 'auth')]
    public function list(int $id, InputManager $input): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUsersPK($id, $this->userRepository->getCommonUserIDs($this->user));
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository
            ->filter($input->query->fetch(['date-from', 'date-to']))
            ->paginate($this->paginationFactory->createPaginator())
            ->findByTagIdWithPagination((int) $tag->id);

        return $this->chargesView->withRelation(Wallet::class)
                                 ->jsonPaginated($charges, $this->chargeRepository->getPaginationState());
    }

    #[Route(route: '/tags/<id:\d+>/charges/total', name: 'tag.charges.total', methods: 'GET', group: 'auth')]
    public function total(int $id, InputManager $input): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUsersPK($id, $this->userRepository->getCommonUserIDs($this->user));
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $this->chargeRepository->filter($input->query->fetch(['date-from', 'date-to']));

        $income = $this->chargeRepository->totalByTagPK((int) $tag->id, Charge::TYPE_INCOME);
        $expense = $this->chargeRepository->totalByTagPK((int) $tag->id, Charge::TYPE_EXPENSE);

        return $this->response->json([
            'data' => [
                'totalAmount' => $this->chargeWalletService->totalByIncomeAndExpense($income, $expense),
                'totalIncomeAmount' => $income,
                'totalExpenseAmount' => $expense,
                'currency' => $this->currencyView->map($this->user->getDefaultCurrency()),
            ],
        ]);
    }

    #[Route(route: '/tags/<id:\d+>/charges/graph', name: 'tag.charges.graph', methods: 'GET', group: 'auth')]
    public function graph(int $id, InputManager $input, ChargeAmountGraph $graph): ResponseInterface
    {
        $tag = $this->tagRepository->findByPKByUsersPK($id, $this->userRepository->getCommonUserIDs($this->user));
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $graph->filter($input->query->fetch(['date-from', 'date-to']));
        $graph->groupBy($input->query('group-by'));

        return $this->response->json([
            'data' => $graph->getGraphByTag($tag),
        ]);
    }
}
