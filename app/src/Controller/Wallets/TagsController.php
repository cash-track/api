<?php

declare(strict_types=1);

namespace App\Controller\Wallets;

use App\Database\Wallet;
use App\Repository\TagRepository;
use App\Repository\WalletRepository;
use App\View\TagsView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class TagsController extends Controller
{
    public function __construct(
        AuthScope $auth,
        private ResponseWrapper $response,
        private WalletRepository $walletRepository,
        private TagRepository $tagRepository,
        private TagsView $tagsView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/wallets/<id>/tags', name: 'wallet.index.tags', methods: 'GET', group: 'auth')]
    public function list(int $id): ResponseInterface
    {
        $wallet = $this->walletRepository->findByPKByUserPK($id, (int) $this->user->id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        $tags = $this->tagRepository->findAllByWalletPK($wallet->id);

        return $this->tagsView->json($tags);
    }
}
