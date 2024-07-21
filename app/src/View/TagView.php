<?php

declare(strict_types=1);

namespace App\View;

use App\Database\Tag;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class TagView
{
    public function __construct(
        protected ResponseWrapper $response,
    ) {
    }

    public function json(Tag $tag): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($tag),
        ]);
    }

    public function map(?Tag $tag): ?array
    {
        if ($tag === null) {
            return null;
        }

        return [
            'type'        => 'tag',
            'id'          => $tag->id,
            'name'        => $tag->name,
            'icon'        => $tag->icon,
            'color'       => $tag->color,
            'userId'      => $tag->userId,
            'createdAt'   => $tag->createdAt->format(DATE_W3C),
            'updatedAt'   => $tag->updatedAt->format(DATE_W3C),
        ];
    }
}
