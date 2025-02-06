<?php

declare(strict_types=1);

namespace Tests\Feature\Request\Wallet;

use App\Database\Currency;
use App\Request\Wallet\CreateRequest;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    public function testCreateWalletDefault(): void
    {
        $request = new CreateRequest($this->getContainer()->get(SluggerInterface::class));
        $request->name = 'Test wallet';

        $wallet = $request->createWallet();

        $this->assertEquals('test-wallet', $wallet->slug);
        $this->assertEquals(Currency::DEFAULT_CURRENCY_CODE, $wallet->defaultCurrencyCode);
    }
}
