<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class WalletsController
{
    use PrototypeTrait;

    /**
     * @Route(route="/wallets", name="wallet.list", methods="GET", group="auth")
     *
     * @return string
     */
    public function list(): array
    {
        /** @var array $wallets */
        $wallets = $this->wallets->findAll();

        return [
            'data' => array_map([$this->walletView, 'map'], $wallets)
        ];
    }

    /**
     * @Route(route="/wallets/<id>", name="wallet.index", methods="GET", group="auth")
     *
     * @param int $id
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function index(int $id): ResponseInterface
    {
        $wallet = $this->wallets->findByPK($id);

        if (! $wallet instanceof Wallet) {
            return $this->response->create(404);
        }

        return $this->walletView->json($wallet);
    }
}
