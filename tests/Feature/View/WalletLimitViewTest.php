<?php

declare(strict_types=1);

namespace Tests\Feature\View;

use App\Service\Limit\WalletLimit;
use App\View\WalletLimitView;
use Psr\Http\Message\ResponseInterface;
use Tests\Factories\LimitFactory;
use Tests\TestCase;

class WalletLimitViewTest extends TestCase
{
    public function testJsonMap(): void
    {
        $limit = LimitFactory::make();
        $walletLimit = new WalletLimit($limit, 123);

        $view = $this->getContainer()->get(WalletLimitView::class);

        $this->assertInstanceOf(ResponseInterface::class, $view->json($walletLimit));
    }

    public function testMapEmpty(): void
    {
        $view = $this->getContainer()->get(WalletLimitView::class);

        $this->assertNull($view->map(null));
    }
}
