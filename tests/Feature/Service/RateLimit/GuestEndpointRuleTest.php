<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\GuestEndpointRule;
use Tests\TestCase;

class GuestEndpointRuleTest extends TestCase
{
    public function testKeyIsScopedBySlugAndIndependentFromDefaultGuestBucket(): void
    {
        $rule = (new GuestEndpointRule('login', 5, 60))->with('1.1.1.1');

        $this->assertEquals('guest:login:1.1.1.1', $rule->key());
        $this->assertNotEquals('guest:1.1.1.1', $rule->key());
    }

    public function testLimitAndTtlArePassedThrough(): void
    {
        $rule = new GuestEndpointRule('register', 3, 30);

        $this->assertEquals(3, $rule->limit());
        $this->assertEquals(30, $rule->ttl());
    }

    public function testDistinctSlugsProduceDistinctKeysForTheSameIp(): void
    {
        $login = (new GuestEndpointRule('login', 5, 60))->with('2.2.2.2');
        $register = (new GuestEndpointRule('register', 3, 60))->with('2.2.2.2');

        $this->assertNotEquals($login->key(), $register->key());
    }
}
