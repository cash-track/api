<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Charges;

use App\Controller\Wallets\Controller;
use App\Database\Charge;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\Request\Charge\CreateRequest;
use App\Service\ChargeWalletService;
use App\Service\Pagination\PaginationFactory;
use App\Service\Statistics\ChargeAmountGraph;
use App\View\ChargesView;
use App\View\ChargeView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

class ChargesController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private PaginationFactory $paginationFactory,
        private ChargesView $chargesView,
        private ChargeView $chargeView,
        private ChargeWalletService $chargeWalletService,
        private ChargeRepository $chargeRepository,
        private WalletRepository $walletRepository,
        private TagRepository $tagRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<walletId:\d+>/charges', name: 'wallet.charge.list', methods: 'GET', group: 'auth')]
    public function list($walletId, InputManager $input): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository
            ->filter($input->query->fetch(['date-from', 'date-to']))
            ->paginate($this->paginationFactory->createPaginator())
            ->findByWalletIdWithPagination((int) $wallet->id);

        return $this->chargesView->jsonPaginated($charges, $this->chargeRepository->getPaginationState());
    }

    #[Route(route: '/wallets/<walletId:\d+>/charges/graph', name: 'wallet.charge.graph', methods: 'GET', group: 'auth')]
    public function graph($walletId, InputManager $input, ChargeAmountGraph $graph)
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $graph->filter($input->query->fetch(['date-from', 'date-to']));
        $graph->groupBy($input->query('group-by'));

        return $this->response->json([
            'data' => $graph->getGraph(wallet: $wallet),
        ]);
    }

    #[Route(route: '/wallets/<walletId:\d+>/charges', name: 'wallet.charge.create', methods: 'POST', group: 'auth')]
    public function create($walletId, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $charge = new Charge();
        $charge->type = $request->getType();
        $charge->amount = $request->getAmount();
        $charge->title = $request->getTitle();
        $charge->description = $request->getDescription();
        $charge->setWallet($wallet);
        $charge->setUser($this->user);

        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->getTags(), $wallet->getUserIDs());

        foreach ($tags as $tag) {
            $charge->tags->add($tag);
        }

        // TODO. Implement currency conversion when charge currency is different that wallet.

        try {
            $this->chargeWalletService->create($wallet, $charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store charge', [
                'action' => 'wallet.charge.create',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to create charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->withRelation(Wallet::class)->json($charge);
    }

    #[Route(route: '/wallets/<walletId:\d+>/charges/<chargeId>', name: 'wallet.charge.update', methods: 'PUT', group: 'auth')]
    public function update($walletId, string $chargeId, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = $this->chargeRepository->findByPKByWalletPK($chargeId, (int) $wallet->id);

        if (! $charge instanceof Charge) {
            return $this->response->create(404);
        }

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $oldCharge = clone $charge;

        $charge->type = $request->getType();
        $charge->amount = $request->getAmount();
        $charge->title = $request->getTitle();
        $charge->description = $request->getDescription();

        $charge->tags->clear();
        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->getTags(), $wallet->getUserIDs());

        foreach ($tags as $tag) {
            $charge->tags->add($tag);
        }

        // TODO. Implement currency conversion when charge currency is different that wallet.

        try {
            $this->chargeWalletService->update($wallet, $oldCharge, $charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store charge', [
                'action' => 'wallet.charge.update',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->withRelation(Wallet::class)->json($charge);
    }

    #[Route(route: '/wallets/<walletId:\d+>/charges/<chargeId>', name: 'wallet.charge.delete', methods: 'DELETE', group: 'auth')]
    public function delete($walletId, string $chargeId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = $this->chargeRepository->findByPKByWalletPK($chargeId, (int) $wallet->id);

        if (! $charge instanceof Charge) {
            return $this->response->create(404);
        }

        try {
            $this->chargeWalletService->delete($wallet, $charge);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete charge', [
                'action' => 'wallet.charge.delete',
                'id'     => $wallet->id,
                'userId' => $this->user->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to delete charge. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
