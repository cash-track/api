<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class ChargeTitlesView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected ChargeTitleView $chargeTitleView,
    ) {
    }

    public function json(array $titles): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($titles),
        ]);
    }

    public function map(array $titles): array
    {
        return array_map([$this->chargeTitleView, 'map'], $titles);
    }
}
