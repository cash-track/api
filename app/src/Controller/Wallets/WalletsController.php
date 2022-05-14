<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Charge;
use App\Database\Currency;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\CurrencyRepository;
use App\Repository\WalletRepository;
use App\Request\Wallet\CreateRequest;
use App\Request\Wallet\SortSetRequest;
use App\Request\Wallet\UpdateRequest;
use App\Service\Sort\SortService;
use App\Service\Sort\SortType;
use App\Service\UserOptionsService;
use App\Service\UserService;
use App\Service\WalletService;
use App\View\WalletsView;
use App\View\WalletView;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class WalletsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private LoggerInterface $logger,
        private WalletRepository $walletRepository,
        private WalletService $walletService,
        private WalletsView $walletsView,
        private WalletView $walletView,
        private CurrencyRepository $currencyRepository,
        private ChargeRepository $chargeRepository,
        private UserOptionsService $userOptionsService,
        private SortService $sortService,
        private UserService $userService,
    ) {
        parent::__construct($auth);
    }

    #[Route(
        route: '/wallets',
        name: 'wallet.list',
        methods: 'GET',
        group: 'auth',
    )]
    public function list(): ResponseInterface
    {
        return $this->walletsView->json($this->walletRepository->findAllByUserPK((int) $this->user->id));
    }

    #[Route(
        route: '/wallets/unarchived',
        name: 'wallet.list.unarchived',
        methods: 'GET',
        group: 'auth',
    )]
    public function listUnArchived(): ResponseInterface
    {
        return $this->walletsView->json(
            $this->walletRepository->findAllByUserPKByArchived((int) $this->user->id, false),
            $this->userOptionsService->getSort($this->user, SortType::Wallets),
        );
    }

    #[Route(
        route: '/wallets/unarchived/sort',
        name: 'wallet.sort.unarchived.set',
        methods: 'POST',
        group: 'auth',
    )]
    public function sortUnArchived(SortSetRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $this->sortService->set($this->user, SortType::Wallets, $request->getSort());
            $this->userService->store($this->user);
        } catch (\Throwable) { }

        return $this->response->create(200);
    }

    #[Route(
        route: '/wallets/archived',
        name: 'wallet.list.archived',
        methods: 'GET',
        group: 'auth',
    )]
    public function listArchived(): ResponseInterface
    {
        return $this->walletsView->json($this->walletRepository->findAllByUserPKByArchived((int) $this->user->id, true));
    }

    #[Route(
        route: '/wallets/<id>',
        name: 'wallet.index',
        methods: 'GET',
        group: 'auth',
    )]
    public function index(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->walletView->json($wallet);
    }

    #[Route(
        route: '/wallets/<id>/total',
        name: 'wallet.index.total',
        methods: 'GET',
        group: 'auth',
    )]
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

    #[Route(
        route: '/wallets',
        name: 'wallet.create',
        methods: 'POST',
        group: 'auth',
    )]
    public function create(CreateRequest $request): ResponseInterface
    {
        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        try {
            $wallet = $this->walletService->create($request->createWallet(), $this->user);
        } catch (\Throwable $exception) {
            return $this->response->json([
                'message' => 'Unable to create new wallet. Please try again later.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return $this->walletView->json($wallet);
    }

    #[Route(
        route: '/wallets/<id>',
        name: 'wallet.update',
        methods: 'PUT',
        group: 'auth',
    )]
    public function update(int $id, UpdateRequest $request): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        if (! $request->isValid()) {
            return $this->response->json([
                'errors' => $request->getErrors(),
            ], 422);
        }

        $wallet->name = $request->getName();
        $wallet->isPublic = $request->getIsPublic();
        $wallet->defaultCurrencyCode = $request->getDefaultCurrencyCode();

        try {
            /** @var \App\Database\Currency|null $defaultCurrency */
            $defaultCurrency = $this->currencyRepository->findByPK($request->getDefaultCurrencyCode());

            if (! $defaultCurrency instanceof Currency) {
                throw new \RuntimeException('Unable to load default currency');
            }

            $wallet->setDefaultCurrency($defaultCurrency);
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to load currency entity', [
                'action' => 'wallet.update',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        try {
            $this->walletService->store($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to store wallet', [
                'action' => 'wallet.update',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to update wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->walletView->json($wallet);
    }

    #[Route(
        route: '/wallets/<id>',
        name: 'wallet.delete',
        methods: 'DELETE',
        group: 'auth',
    )]
    public function delete(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        try {
            $this->walletService->delete($wallet);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to delete wallet', [
                'action' => 'wallet.delete',
                'id'     => $wallet->id,
                'msg'    => $exception->getMessage(),
            ]);

            return $this->response->json([
                'message' => 'Unable to delete wallet. Please try again later.',
                'error'   => $exception->getMessage(),
            ], 500);
        }

        return $this->response->create(200);
    }
}
