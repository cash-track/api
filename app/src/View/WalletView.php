<?php

declare(strict_types = 1);

namespace App\View;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="walletView")
 */
class WalletView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Wallet $wallet
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(Wallet $wallet): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($wallet),
        ], 200);
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @return array
     */
    public function map(Wallet $wallet): array
    {
        return [
            'type'        => 'wallet',
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
            'defaultCurrency'     => $this->currencyView->map($wallet->defaultCurrency),

            'users' => $wallet->users->count() ? $this->usersView->map($wallet->users->getValues()) : [],
        ];
    }
}
