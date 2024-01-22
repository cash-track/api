<?php

declare(strict_types=1);

namespace App\Controller\Charges;

use App\Controller\AuthAwareController;
use App\Repository\ChargeRepository;
use App\View\ChargeTitlesView;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Http\ResponseWrapper;
use Spiral\Router\Annotation\Route;

final class SuggestionsController extends AuthAwareController
{
    public function __construct(
        AuthScope $auth,
        protected ResponseWrapper $response,
        private readonly ChargeRepository $chargeRepository,
        private readonly ChargeTitlesView $chargeTitlesView,
    ) {
        parent::__construct($auth);
    }

    #[Route(route: '/charges/title/suggestions/<query>', name: 'charges.title.suggestions', methods: 'GET', group: 'auth')]
    public function suggestions(string $query = ''): ResponseInterface
    {
        return $this->chargeTitlesView->json(
            $this->chargeRepository->searchTitle((int) $this->user->id, urldecode($query))
        );
    }
}
