<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\UserView;
use Tests\TestCase;

class UserViewTest extends TestCase
{
    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(UserView::class);

        $this->assertNull($view->map(null));
    }
}
