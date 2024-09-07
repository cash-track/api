<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Limit;
use App\Database\Wallet;
use Tests\TestCase;

class WalletTest extends TestCase
{
    public function testGetUsersVerifyType(): void
    {
        $wallet = new Wallet();
        $wallet->users->add(null);

        $this->assertCount(0, $wallet->getUsers());
    }

    public function testGetUserIDsVerifyType(): void
    {
        $wallet = new Wallet();
        $wallet->users->add(null);

        $this->assertCount(0, $wallet->getUserIDs());
    }

    public function testGetLimitsVerifyType(): void
    {
        $wallet = new Wallet();
        $wallet->limits->add(null);
        $wallet->limits->add(new Limit());

        $this->assertCount(1, $wallet->getLimits());
    }
}
