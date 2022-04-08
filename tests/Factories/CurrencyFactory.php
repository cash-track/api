<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Currency;
use Tests\Fixtures;

class CurrencyFactory extends AbstractFactory
{
    public function find(string $code): Currency
    {
        $currency = $this->currencyRepository->findByPK($code);

        if ($currency instanceof Currency) {
            return $currency;
        }

        throw new \RuntimeException("Unable to resolve {$code} currency");
    }

    public static function supportedCodes(): array
    {
        return [
            'USD',
            'EUR',
            'UAH',
        ];
    }

    public static function code(): string
    {
        return Fixtures::arrayElement(self::supportedCodes());
    }
}
