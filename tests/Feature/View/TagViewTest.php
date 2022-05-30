<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\TagView;
use Tests\TestCase;

class TagViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(TagView::class);

        $this->assertNull($view->map(null));
    }
}
