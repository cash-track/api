<?php

declare(strict_types=1);

namespace Feature\View;

use App\View\ChargeTitleView;
use Tests\TestCase;

class ChargeTitleViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(ChargeTitleView::class);

        $this->assertNull($view->map(null));
    }
}
