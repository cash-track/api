<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\EmailConfirmationView;
use Tests\TestCase;

class EmailConfirmationViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(EmailConfirmationView::class);

        $this->assertNull($view->map(null));
    }
}
