<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="currenciesView")
 */
class CurrenciesView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\Currency[] $currencies
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(array $currencies): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($currencies),
        ], 200);
    }

    /**
     * @param \App\Database\Currency[] $currencies
     * @return array
     */
    public function map(array $currencies): array
    {
        return array_map([$this->currencyView, 'map'], $currencies);
    }
}
