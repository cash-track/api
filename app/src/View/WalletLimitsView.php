<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class WalletLimitsView
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected WalletLimitView $walletLimitView,
    ) {
    }

    public function json(array $limits): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($limits),
        ]);
    }

    public function map(array $limits): array
    {
        $this->walletLimitView->withRelations($this->relations);

        return array_map([$this->walletLimitView, 'map'], $limits);
    }
}
