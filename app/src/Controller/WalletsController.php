<?php

declare(strict_types=1);

namespace App\Controller;

use App\Annotation\Route;
use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;

class WalletsController
{
    use PrototypeTrait;

    /**
     * @Route(action="/wallets", verbs={"GET"})
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
     * @Route(action="/wallets/<id>", verbs={"GET"})s
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
