<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CurrencyRepository;
use App\View\CurrenciesView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthContextInterface;
use Spiral\Router\Annotation\Route;

final class CurrencyController extends AuthAwareController
{
    public function __construct(
        AuthContextInterface $auth,
        protected CurrencyRepository $currencyRepository,
        protected CurrenciesView $currenciesView,
    ) {
        parent::__construct($auth);
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
