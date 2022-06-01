<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

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
}
