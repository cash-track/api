<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Currency;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class CurrencyView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
    ) {
    }

    public function map(?Currency $currency): ?array
    {
        if ($currency === null) {
            return null;
        }

        return [
            'type'      => 'currency',
            'id'        => $currency->code,
            'code'      => $currency->code,
            'name'      => $currency->name,
            'char'      => $currency->char,
            'rate'      => $currency->rate,
            'updatedAt' => $currency->updatedAt->format(DATE_W3C),
        ];
    }
}
