<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Tag;
use App\Database\User;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class ChargesView
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected ChargeView $chargeView,
    ) {
        $this->withRelations([User::class, Tag::class]);
    }

    public function jsonPaginated(array $charges, array $paginationState): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($charges),
            'pagination' => $paginationState,
        ]);
    }

    public function map(array $charges): array
    {
        $this->chargeView->withRelations($this->relations);

        return array_map([$this->chargeView, 'map'], $charges);
    }
}
