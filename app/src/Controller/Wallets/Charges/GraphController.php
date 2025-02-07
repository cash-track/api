<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Charges;

use App\Controller\Wallets\Controller;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\Service\ChargeWalletService;
use App\Service\Pagination\PaginationFactory;
use App\Service\Statistics\ChargeAmountGraph;
use App\Service\Statistics\ChargeTotalGraph;
use App\View\ChargesView;
use App\View\ChargeView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Spiral\Translator\Traits\TranslatorTrait;

final class GraphController extends Controller
{
    use TranslatorTrait;

    public function __construct(
        AuthContextInterface $auth,
        private readonly ResponseWrapper $response,
        private readonly LoggerInterface $logger,
        private readonly PaginationFactory $paginationFactory,
        private readonly ChargesView $chargesView,
        private readonly ChargeView $chargeView,
        private readonly ChargeWalletService $chargeWalletService,
        private readonly ChargeRepository $chargeRepository,
        private readonly WalletRepository $walletRepository,
        private readonly TagRepository $tagRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<walletId>/charges/graph/amount', name: 'wallet.charge.graph.amount', methods: 'GET', group: 'auth')]
    public function amount(string $walletId, InputManager $input, ChargeAmountGraph $graph): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $graph->filter($input->query->fetch(['date-from', 'date-to']));
        $graph->groupBy($input->query('group-by'));
        $graph->groupByTags($this->fetchFilteredTagIDs($input));

        return $this->response->json([
            'data' => $graph->getGraph(wallet: $wallet),
        ]);
    }

    #[Route(route: '/wallets/<walletId>/charges/graph/total', name: 'wallet.charge.graph.total', methods: 'GET', group: 'auth')]
    public function total(string $walletId, InputManager $input, ChargeTotalGraph $graph): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $graph->filter($input->query->fetch(['date-from', 'date-to', 'charge-type']));
        $graph->groupByTags($this->fetchFilteredTagIDs($input));

        return $this->response->json([
            'data' => $graph->getGraph($wallet),
        ]);
    }
}
