<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\UserRule;
use Tests\TestCase;

class UserRuleTest extends TestCase
{
    public function testKey()
    {
        $this->assertEquals('user:', (new UserRule())->key());
        $this->assertEquals('user:123', (new UserRule())->with('123')->key());
        $this->assertEquals('user:123-1.1.1.1', (new UserRule())->with('123', '1.1.1.1')->key());
    }
}
