<?php

declare(strict_types=1);

namespace Tests\Factories;

use Tests\Fixtures;

class CurrencyFactory extends AbstractFactory
{
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
