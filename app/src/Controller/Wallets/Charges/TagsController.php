<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Charges;

use App\Controller\Wallets\Controller;
use App\Database\Tag;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Service\Pagination\PaginationFactory;
use App\Service\Statistics\ChargeAmountGraph;
use App\View\ChargesView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

class TagsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private PaginationFactory $paginationFactory,
        private ChargesView $chargesView,
        private ChargeRepository $chargeRepository,
        private WalletRepository $walletRepository,
        private TagRepository $tagRepository,
        private UserRepository $userRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<walletId>/tags/<tagId>/charges', name: 'wallet.tag.charge.list', methods: 'GET', group: 'auth')]
    public function list(int $walletId, int $tagId, InputManager $input): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);
        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tag = $this->tagRepository->findByPKByUsersPK($tagId, $wallet->getUserIDs());
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository
            ->filter($input->query->fetch(['date-from', 'date-to']))
            ->paginate($this->paginationFactory->createPaginator())
            ->findByWalletIdAndTagIdWithPagination((int) $wallet->id, (int) $tag->id);

        return $this->chargesView->jsonPaginated($charges, $this->chargeRepository->getPaginationState());
    }

    #[Route(route: '/wallets/<walletId>/tags/<tagId>/charges/graph', name: 'wallet.tag.charge.graph', methods: 'GET', group: 'auth')]
    public function graph(int $walletId, int $tagId, InputManager $input, ChargeAmountGraph $graph): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($walletId, (int) $this->user->id);
        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tag = $this->tagRepository->findByPKByUsersPK($tagId, $this->userRepository->getCommonUserIDs($this->user));
        if (! $tag instanceof Tag) {
            return $this->response->create(404);
        }

        $graph->filter($input->query->fetch(['date-from', 'date-to']));
        $graph->groupBy($input->query('group-by'));

        return $this->response->json([
            'data' => $graph->getGraph(wallet: $wallet, tag: $tag),
        ]);
    }
}
