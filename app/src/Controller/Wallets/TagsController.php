<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\View\TagsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class TagsController extends Controller
{
    public function __construct(
        AuthContextInterface $auth,
        private readonly ResponseWrapper $response,
        private readonly WalletRepository $walletRepository,
        private readonly TagRepository $tagRepository,
        private readonly TagsView $tagsView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/tags', name: 'wallet.tags.list', methods: 'GET', group: 'auth')]
    public function list(string $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tags = $this->tagRepository->findAllByWalletPK((int) $wallet->id);

        return $this->tagsView->json($tags);
    }

    #[Route(route: '/wallets/<walletId>/tags/find/<query>', name: 'wallet.tags.find', methods: 'GET', group: 'auth')]
    public function find(string $walletId, string $query = ''): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK((int) $walletId, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tags = $this->tagRepository->searchAllByUsersPK($wallet->getUserIDs(), urldecode($query));

        return $this->tagsView->json($tags);
    }
}
