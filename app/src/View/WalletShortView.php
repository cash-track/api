<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class WalletShortView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected CurrencyView $currencyView,
    ) {
    }

    public function json(Wallet $wallet): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($wallet),
        ]);
    }

    public function map(?Wallet $wallet): ?array
    {
        if ($wallet === null) {
            return null;
        }

        return [
            'type'        => 'walletShort',
            'id'          => $wallet->id,
            'name'        => $wallet->name,
            'slug'        => $wallet->slug,
            'totalAmount' => $wallet->totalAmount,
            'isActive'    => $wallet->isActive,
            'isPublic'    => $wallet->isPublic,
            'isArchived'  => $wallet->isArchived,
            'createdAt'   => $wallet->createdAt->format(DATE_W3C),
            'updatedAt'   => $wallet->updatedAt->format(DATE_W3C),

            'defaultCurrencyCode' => $wallet->defaultCurrencyCode,
            'defaultCurrency'     => $this->currencyView->map($wallet->getDefaultCurrency()),
        ];
    }
}
