<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class Currencies extends Fixture
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
        return self::arrayElement(self::supportedCodes());
    }
}