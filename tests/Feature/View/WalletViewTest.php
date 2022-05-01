<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\WalletView;
use Tests\TestCase;

class WalletViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(WalletView::class);

        $this->assertNull($view->map(null));
    }
}
