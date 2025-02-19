<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\PasskeyView;
use Tests\TestCase;

class PasskeyViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(PasskeyView::class);

        $this->assertNull($view->map(null));
    }
}
