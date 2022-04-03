<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Http\ResponseWrapper;

class ChargesView implements SingletonInterface
{
    public function __construct(
        protected ResponseWrapper $response,
        protected ChargeView $chargeView,
    ) {
    }

    public function json(array $charges): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charges),
        ], 200);
    }

    public function jsonPaginated(array $charges, array $paginationState): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charges),
            'pagination' => $paginationState,
        ], 200);
    }

    public function map(array $charges): array
    {
        return array_map([$this->chargeView, 'map'], $charges);
    }
}
