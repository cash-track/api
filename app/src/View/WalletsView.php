<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class WalletsView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected WalletView $walletView,
    ) {
    }

    public function json(array $wallets): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($wallets),
        ], 200);
    }

    public function map(array $wallets): array
    {
        return array_map([$this->walletView, 'map'], $wallets);
    }
}
