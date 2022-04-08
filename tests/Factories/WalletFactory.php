<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Currency;
use App\Database\User;
use App\Database\Wallet;
use Tests\Fixtures;

class WalletFactory extends AbstractFactory
{
    protected ?User $user = null;

    public function forUser(?User $user): WalletFactory
    {
        $this->user = $user;

        return $this;
    }

    public function create(Wallet $wallet = null): Wallet
    {
        $wallet = $wallet ?? self::make();

        $currency = $this->currencyRepository->findByPK($wallet->defaultCurrencyCode);

        if ($currency instanceof Currency) {
            $wallet->setDefaultCurrency($currency);
        } else {
            $wallet->setDefaultCurrency($this->currencyRepository->getDefault());
        }

        if ($this->user !== null) {
            $wallet->users->add($this->user);
        }

        $this->persist($wallet);

        return $wallet;
    }

    public static function make(): Wallet
    {
        $wallet = new Wallet();

        $wallet->name = Fixtures::string();
        $wallet->slug = Fixtures::string();
        $wallet->createdAt = Fixtures::dateTime();
        $wallet->updatedAt = Fixtures::dateTimeAfter($wallet->createdAt);
        $wallet->defaultCurrencyCode = CurrencyFactory::code();

        return $wallet;
    }

    public static function archived(Wallet $wallet = null): Wallet
    {
        if ($wallet === null) {
            $wallet = self::make();
        }

        $wallet->isArchived = true;

        return $wallet;
    }

    public static function inactive(Wallet $wallet = null): Wallet
    {
        if ($wallet === null) {
            $wallet = self::make();
        }

        $wallet->isActive = false;

        return $wallet;
    }
}
