<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Currency;
use App\Database\CurrencyExchange;
use Tests\Factories\CurrencyFactory;
use Tests\TestCase;

class CurrencyExchangeTest extends TestCase
{
    public function testNew(): void
    {
        $currencyExchange = new CurrencyExchange();

        $this->assertInstanceOf(Currency::class, $currencyExchange->getSrcCurrency());
        $this->assertInstanceOf(Currency::class, $currencyExchange->getDstCurrency());

        $currency = $this->getContainer()->get(CurrencyFactory::class)->find(CurrencyFactory::code());

        $currencyExchange->setSrcCurrency($currency);
        $currencyExchange->setDstCurrency($currency);
    }
}
