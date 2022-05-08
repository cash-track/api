<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\CurrencyView;
use Tests\TestCase;

class CurrencyViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(CurrencyView::class);

        $this->assertNull($view->map(null));
    }
}
