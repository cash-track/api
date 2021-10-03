<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Wallet;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="walletView")
 */
class WalletView implements SingletonInterface
{
    /**
     * @var \Spiral\Http\ResponseWrapper
     */
    protected ResponseWrapper $response;

    /**
     * @var \App\View\CurrencyView
     */
    protected CurrencyView $currency;

    /**
     * @var \App\View\UsersView
     */
    protected UsersView $users;

    /**
     * @var \App\View\ChargesView
     */
    protected ChargesView $charges;

    /**
     * @param \Spiral\Http\ResponseWrapper $response
     * @param \App\View\CurrencyView $currencyView
     * @param \App\View\UsersView $usersView
     * @param \App\View\ChargesView $chargesView
     */
    public function __construct(
        ResponseWrapper $response,
        CurrencyView $currencyView,
        UsersView $usersView,
        ChargesView $chargesView
    ) {
        $this->response = $response;
        $this->currency = $currencyView;
        $this->users = $usersView;
        $this->charges = $chargesView;
    }

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
            'defaultCurrency'     => $this->currency->map($wallet->defaultCurrency),

            'users' => $wallet->users->count() ? $this->users->map($wallet->users->getValues()) : [],

            'latestCharges' => $wallet->latestCharges !== null ? $this->charges->map($wallet->latestCharges->getValues()) : [],
        ];
    }
}
