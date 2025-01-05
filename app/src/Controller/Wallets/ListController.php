<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Repository\WalletRepository;
use App\Request\Wallet\SortSetRequest;
use App\Service\Sort\SortService;
use App\Service\Sort\SortType;
use App\Service\UserOptionsService;
use App\Service\UserService;
use App\View\WalletsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\Request\InputManager;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class ListController extends Controller
{
    public function __construct(
        AuthContextInterface $auth,
        private ResponseWrapper $response,
        private WalletRepository $walletRepository,
        private WalletsView $walletsView,
        private UserOptionsService $userOptionsService,
        private SortService $sortService,
        private UserService $userService,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets', name: 'wallet.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        return $this->walletsView->json($this->walletRepository->findAllByUserPK((int) $this->user->id));
    }

    #[Route(route: '/wallets/unarchived', name: 'wallet.list.unarchived', methods: 'GET', group: 'auth', priority: -1)]
    public function listUnArchived(): ResponseInterface
    {
        return $this->walletsView->json(
            $this->walletRepository->findAllByUserPKByArchived((int) $this->user->id, false),
            $this->userOptionsService->getSort($this->user, SortType::Wallets),
        );
    }

    #[Route(route: '/wallets/unarchived/sort', name: 'wallet.sort.unarchived.set', methods: 'POST', group: 'auth')]
    public function sortUnArchived(SortSetRequest $request): ResponseInterface
    {
        try {
            $this->sortService->set($this->user, SortType::Wallets, $request->sort);
            $this->userService->store($this->user);
        } catch (\Throwable) {
        }

        return $this->response->create(200);
    }

    #[Route(route: '/wallets/archived', name: 'wallet.list.archived', methods: 'GET', group: 'auth', priority: -1)]
    public function listArchived(): ResponseInterface
    {
        return $this->walletsView->json(
            $this->walletRepository->findAllByUserPKByArchived((int) $this->user->id, true)
        );
    }

    #[Route(route: '/wallets/has-limits', name: 'wallet.list.has-limits', methods: 'GET', group: 'auth', priority: -1)]
    public function listHasLimits(InputManager $input): ResponseInterface
    {
        return $this->walletsView->json(
            $this->walletRepository->findAllHasLimitsByUserPK((int) $this->user->id, $input->query->has('archived'))
        );
    }
}
