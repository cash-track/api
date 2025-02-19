<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\User;
use Tests\Fixtures;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testGetFullName(): void
    {
        $user = new User();
        $user->name = Fixtures::string();

        $this->assertEquals($user->name, $user->fullName());
    }
}
