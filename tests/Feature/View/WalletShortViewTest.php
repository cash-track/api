<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\View\WalletShortView;
use Psr\Http\Message\ResponseInterface;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class WalletShortViewTest extends TestCase
{
    public function testJson(): void
    {
        $wallet = WalletFactory::make();

        $view = $this->getContainer()->get(WalletShortView::class);

        $this->assertInstanceOf(ResponseInterface::class, $view->json($wallet));
    }

    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(WalletShortView::class);

        $this->assertNull($view->map(null));
    }
}
