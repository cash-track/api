<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CurrencyRepository;
use App\View\CurrenciesView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Router\Annotation\Route;

final class CurrencyController extends AuthAwareController
{
    public function __construct(
        AuthScope $authScope,
        protected CurrencyRepository $currencyRepository,
        protected CurrenciesView $currenciesView,
    ) {
        parent::__construct($authScope);
    }

    #[Route(route: '/currencies', name: 'currency.list', methods: 'GET', group: 'auth')]
    public function list(): ResponseInterface
    {
        /** @var \App\Database\Currency[] $currencies */
        $currencies = $this->currencyRepository->findAll();

        return $this->currenciesView->json($currencies);
    }

    #[Route(route: '/currencies/featured', name: 'currency.featured', methods: 'GET', group: 'auth')]
    public function featured(): ResponseInterface
    {
        /** @var \App\Database\Currency[] */
        $currencies = $this->currencyRepository->getFeatured();

        return $this->currenciesView->json($currencies);
    }
}
