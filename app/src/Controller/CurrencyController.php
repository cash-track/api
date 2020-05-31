<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class CurrencyController
{
    use PrototypeTrait;

    /**
     * @Route(route="/currencies", name="currency.list", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function list(): ResponseInterface
    {
        /** @var \App\Database\Currency[] $currencies */
        $currencies = $this->currencies->findAll();

        return $this->currenciesView->json($currencies);
    }

    /**
     * @Route(route="/currencies/featured", name="currency.featured", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function featured(): ResponseInterface
    {
        /** @var \App\Database\Currency[] */
        $currencies = $this->currencies->getFeatured();

        return $this->currenciesView->json($currencies);
    }
}
