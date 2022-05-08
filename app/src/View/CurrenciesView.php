<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class CurrenciesView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected CurrencyView $currencyView,
    ) {
    }

    public function json(array $currencies): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($currencies),
        ], 200);
    }

    public function map(array $currencies): array
    {
        return array_map([$this->currencyView, 'map'], $currencies);
    }
}
