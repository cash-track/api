<?php

declare(strict_types=1);

namespace App\View;

use App\Service\Sort\Sorter;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class WalletsView
{
    use Sorter;

    public function __construct(
        protected ResponseWrapper $response,
        protected WalletView $walletView,
    ) {
    }

    public function json(array $wallets, array $sort = null): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($this->applySort($wallets, $sort)),
        ]);
    }

    public function map(array $wallets): array
    {
        return array_map([$this->walletView, 'map'], $wallets);
    }
}
