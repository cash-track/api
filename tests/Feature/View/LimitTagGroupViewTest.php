<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\Database\LimitTagGroup;
use App\View\LimitTagGroupView;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class LimitTagGroupViewTest extends TestCase
{
    public function testJson(): void
    {
        $tagGroup = new LimitTagGroup();
        $tagGroup->connection = LimitTagGroup::CONNECTION_OR;

        $view = $this->getContainer()->get(LimitTagGroupView::class);

        $this->assertInstanceOf(ResponseInterface::class, $view->json([$tagGroup]));
    }
}
