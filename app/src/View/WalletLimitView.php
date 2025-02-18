<?php

declare(strict_types=1);

namespace App\View;

use App\Service\Limit\WalletLimit;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class WalletLimitView
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected LimitView $limitView,
        protected TagsView $tagsView,
        protected WalletShortView $walletShortView,
    ) {
    }

    public function json(WalletLimit $limit): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($limit),
        ]);
    }

    public function map(?WalletLimit $limit): ?array
    {
        if ($limit === null) {
            return null;
        }

        return [
            'type'       => 'walletLimit',
            'amount'     => $limit->amount,
            'percentage' => $limit->percentage,
            'limit'      => $this->limitView->map($limit->limit),
        ];
    }
}
