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
use App\Request\Charge\MoveRequest;
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
use Spiral\Translator\Traits\TranslatorTrait;

class ChargesController extends Controller
{
    use TranslatorTrait;

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

    #[Route(route: '/wallets/<walletId>/charges', name: 'wallet.charge.list', methods: 'GET', group: 'auth')]
    public function list(string $walletId, InputManager $input): ResponseInterface
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

    #[Route(route: '/wallets/<walletId>/charges/graph', name: 'wallet.charge.graph', methods: 'GET', group: 'auth')]
    public function graph(string $walletId, InputManager $input, ChargeAmountGraph $graph): ResponseInterface
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
    public function create(string $walletId, CreateRequest $request): ResponseInterface
    {
        $this->verifyIsProfileConfirmed();

        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = new Charge();
        $charge->type = $request->type;
        $charge->amount = $request->amount;
        $charge->title = $request->title;
        $charge->description = $request->description;
        $charge->setWallet($wallet);
        $charge->setUser($this->user);

        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->tags, $wallet->getUserIDs());

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
                'message' => $this->say('charge_create_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->withRelation(Wallet::class)->json($charge);
    }

    #[Route(route: '/wallets/<walletId>/charges/<chargeId>', name: 'wallet.charge.update', methods: 'PUT', group: 'auth')]
    public function update(string $walletId, string $chargeId, CreateRequest $request): ResponseInterface
    {
        $this->verifyIsProfileConfirmed();

        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charge = $this->chargeRepository->findByPKByWalletPK($chargeId, (int) $wallet->id);

        if (! $charge instanceof Charge) {
            return $this->response->create(404);
        }

        $oldCharge = clone $charge;

        $charge->type = $request->type;
        $charge->amount = $request->amount;
        $charge->title = $request->title;
        $charge->description = $request->description;

        if (($dateTime = $request->getDateTime()) !== null) {
            $charge->createdAt = $dateTime;
        }

        $charge->tags->clear();
        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->tags, $wallet->getUserIDs());

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
                'message' => $this->say('charge_update_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->chargeView->withRelation(Wallet::class)->json($charge);
    }

    #[Route(route: '/wallets/<walletId>/charges/<chargeId>', name: 'wallet.charge.delete', methods: 'DELETE', group: 'auth')]
    public function delete(string $walletId, string $chargeId): ResponseInterface
    {
        $this->verifyIsProfileConfirmed();

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
                'message' => $this->say('charge_delete_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }

    #[Route(route: '/wallets/<walletId>/charges/move/<targetWalletId>', name: 'wallet.charges.move', methods: 'POST', group: 'auth')]
    public function move(string $walletId, string $targetWalletId, MoveRequest $request): ResponseInterface
    {
        $this->verifyIsProfileConfirmed();

        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $targetWallet = $this->walletRepository->findByPKByUserPK((int) $targetWalletId, (int) $this->user->id);

        if (! $targetWallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $charges = $this->chargeRepository->findByPKsByWalletPK($request->chargeIds, (int) $wallet->id);

        try {
            $this->chargeWalletService->move($wallet, $targetWallet, $charges);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to move charges', [
                'action'    => 'wallet.charges.move',
                'id'        => $wallet->id,
                'targetId'  => $targetWallet->id,
                'chargeIds' => $request->chargeIds,
                'userId'    => $this->user->id,
                'msg'       => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('charge_update_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
