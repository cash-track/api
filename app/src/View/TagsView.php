<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class TagsView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected TagView $tagView,
    ) {
    }

    public function json(array $tags): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($tags)
        ]);
    }

    public function map(array $tags): array
    {
        return array_map([$this->tagView, 'map'], $tags);
    }
}
