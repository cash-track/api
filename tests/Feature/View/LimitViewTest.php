<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\LimitView;
use Tests\TestCase;

class LimitViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(LimitView::class);

        $this->assertNull($view->map(null));
    }
}
