<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\Rule;
use Tests\TestCase;

class RuleTest extends TestCase
{
    public function testKey()
    {
        $this->assertEquals('', (new Rule())->key());
    }

    public function testWithLimit()
    {
        $rule = new Rule(limit: 100);
        $other = $rule->withLimit(101);

        $this->assertNotEquals($rule, $other);
        $this->assertNotEquals($rule->limit(), $other->limit());
    }

    public function testWithTtl()
    {
        $rule = new Rule(ttl: 100);
        $other = $rule->withTtl(101);

        $this->assertNotEquals($rule, $other);
        $this->assertNotEquals($rule->ttl(), $other->ttl());
    }
}
