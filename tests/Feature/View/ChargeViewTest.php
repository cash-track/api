<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\ChargeView;
use Tests\TestCase;

class ChargeViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(ChargeView::class);

        $this->assertNull($view->map(null));
    }
}
