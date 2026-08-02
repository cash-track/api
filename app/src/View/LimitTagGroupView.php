<?php

declare(strict_types=1);

namespace App\View;

use App\Database\LimitTagGroup;
use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
final class LimitTagGroupView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected TagsView $tagsView,
    ) {
    }

    /**
     * @param array<array-key, LimitTagGroup> $tagGroups
     */
    public function json(array $tagGroups): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($tagGroups),
        ]);
    }

    /**
     * @param array<array-key, LimitTagGroup> $tagGroups
     */
    public function map(array $tagGroups): array
    {
        return array_map([$this, 'mapOne'], $tagGroups);
    }

    private function mapOne(LimitTagGroup $tagGroup): array
    {
        return [
            'type'       => 'limitTagGroup',
            'connection' => $tagGroup->connection,
            'tags'       => $this->tagsView->map($tagGroup->getTags()),
        ];
    }
}
