<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Tag;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class LimitsView
{
    use Relations;

    public function __construct(
        protected ResponseWrapper $response,
        protected LimitView $limitView,
    ) {
        $this->withRelations([Tag::class]);
    }

    public function json(array $limits): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($limits),
        ]);
    }

    public function map(array $limits): array
    {
        $this->limitView->withRelations($this->relations);

        return array_map([$this->limitView, 'map'], $limits);
    }
}
