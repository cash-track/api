<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\Database\LimitTagGroup;
use App\View\LimitView;
use Tests\Factories\LimitFactory;
use Tests\TestCase;

class LimitViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(LimitView::class);

        $this->assertNull($view->map(null));
    }

    public function testMapReturnsTagGroups(): void
    {
        $limit = LimitFactory::make();

        $tagGroup = new LimitTagGroup();
        $tagGroup->connection = LimitTagGroup::CONNECTION_AND;
        $limit->tagGroups[] = $tagGroup;

        $view = $this->getContainer()->get(LimitView::class);

        $data = $view->map($limit);

        $this->assertIsArray($data);
        $this->assertSame([], $data['tags']);
        $this->assertCount(1, $data['tagGroups']);
        $this->assertSame('and', $data['tagGroups'][0]['connection']);
    }
}
