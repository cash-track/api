<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\UserShortView;
use Psr\Http\Message\ResponseInterface;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class UserShortViewTest extends TestCase
{
    public function testJson(): void
    {
        $user = UserFactory::make();

        $view = $this->getContainer()->get(UserShortView::class);

        $this->assertInstanceOf(ResponseInterface::class, $view->json($user));
    }

    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(UserShortView::class);

        $this->assertNull($view->map(null));
    }
}
