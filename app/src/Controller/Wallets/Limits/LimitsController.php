<?php

declare(strict_types=1);

namespace App\Controller\Wallets\Limits;

use App\Controller\Wallets\Controller;
use App\Database\Limit;
use App\Database\Wallet;
use App\Repository\LimitRepository;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\Request\Limit\CreateRequest;
use App\Service\Limit\LimitService;
use App\View\LimitView;
use App\View\WalletLimitsView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;
use Throwable;

final class LimitsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private readonly ResponseWrapper $response,
        private LoggerInterface $logger,
        private readonly WalletRepository $walletRepository,
        private readonly LimitRepository $limitRepository,
        private readonly LimitService $limitService,
        private readonly LimitView $limitView,
        private readonly WalletLimitsView $walletLimitsView,
        private readonly TagRepository $tagRepository,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/limits', name: 'wallet.limit.index', methods: 'GET', group: 'auth')]
    public function index(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $limits = $this->limitRepository->findAllByWalletPK((int) $wallet->id);
        $limits = $this->limitService->calculate($limits);

        return $this->walletLimitsView->json($limits);
    }

    #[Route(route: '/wallets/<id>/limits', name: 'wallet.limit.create', methods: 'POST', group: 'auth')]
    public function create(string $id, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $limit = new Limit();
        $limit->type = $request->type;
        $limit->amount = $request->amount;
        $limit->setWallet($wallet);

        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->tags, $wallet->getUserIDs());

        foreach ($tags as $tag) {
            $limit->tags->add($tag);
        }

        try {
            $this->limitService->store($limit);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to store limit', [
                'action'   => 'wallet.limit.create',
                'walletId' => $wallet->id,
                'userId'   => $this->user->id,
                'msg'      => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('limit_create_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->limitView->json($limit);
    }

    #[Route(route: '/wallets/<walletId>/limits/<limitId>', name: 'wallet.limit.update', methods: 'PUT', group: 'auth')]
    public function update(string $walletId, string $limitId, CreateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $limit = $this->limitRepository->findByPKByWalletPK((int) $limitId, (int) $wallet->id);

        if (! $limit instanceof Limit) {
            return $this->response->create(404);
        }

        $limit->type = $request->type;
        $limit->amount = $request->amount;

        $limit->tags->clear();
        $tags = $this->tagRepository->findAllByPKsAndUserPKs($request->tags, $wallet->getUserIDs());

        foreach ($tags as $tag) {
            $limit->tags->add($tag);
        }

        try {
            $this->limitService->store($limit);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to store limit', [
                'action'   => 'wallet.limit.update',
                'walletId' => $wallet->id,
                'limitId'  => $limit->id,
                'userId'   => $this->user->id,
                'msg'      => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('limit_update_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->limitView->json($limit);
    }

    #[Route(route: '/wallets/<walletId>/limits/<limitId>', name: 'wallet.limit.delete', methods: 'DELETE', group: 'auth')]
    public function delete(string $walletId, string $limitId): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $limit = $this->limitRepository->findByPKByWalletPK((int) $limitId, (int) $wallet->id);

        if (! $limit instanceof Limit) {
            return $this->response->create(404);
        }

        try {
            $this->limitService->delete($limit);
        } catch (Throwable $exception) {
            $this->logger->error('Unable to delete limit', [
                'action' => 'wallet.limit.delete',
                'id'     => $limitId,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => $this->say('limit_delete_exception'),
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
