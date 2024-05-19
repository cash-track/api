<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Limit;
use App\Database\Wallet;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class LimitTest extends TestCase
{
    public function testGetWallet(): void
    {
        $limit = new Limit();

        $wallet = WalletFactory::make();

        $limit->setWallet($wallet);

        $this->assertInstanceOf(Wallet::class, $limit->getWallet());
    }

    public function testGetTagsVerifyType(): void
    {
        $limit = new Limit();
        $limit->tags->add(null);

        $this->assertCount(0, $limit->getTags());
    }
}
