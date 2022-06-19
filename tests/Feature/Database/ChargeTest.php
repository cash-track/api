<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Charge;
use App\Database\CurrencyExchange;
use App\Database\Wallet;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class ChargeTest extends TestCase
{
    public function testNew(): void
    {
        $charge = new Charge();

        $wallet = WalletFactory::make();

        $charge->setWallet($wallet);

        $this->assertInstanceOf(Wallet::class, $charge->getWallet());

        $currencyExchange = new CurrencyExchange();

        $charge->setCurrencyExchange($currencyExchange);

        $this->assertInstanceOf(CurrencyExchange::class, $charge->getCurrencyExchange());
    }

    public function testGetTagsVerifyType(): void
    {
        $charge = new Charge();
        $charge->tags->add(null);

        $this->assertCount(0, $charge->getTags());
    }
}
