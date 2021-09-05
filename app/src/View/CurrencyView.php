<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Currency;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="currencyView")
 */
class CurrencyView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Currency $currency
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(Currency $currency): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($currency),
        ], 200);
    }

    /**
     * @param \App\Database\Currency $currency
     * @return array
     */
    public function map(Currency $currency): array
    {
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
