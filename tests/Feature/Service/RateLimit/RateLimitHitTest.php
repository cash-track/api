<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\RateLimitHit;
use App\Service\RateLimit\UserRule;
use Tests\TestCase;

class RateLimitHitTest extends TestCase
{
    public function testGetRule()
    {
        $hit = new RateLimitHit($rule = new UserRule());

        $this->assertEquals($rule, $hit->getRule());
    }
}
