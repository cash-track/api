<?php

declare(strict_types=1);

namespace Tests\Feature\Service\RateLimit;

use App\Service\RateLimit\GuestEndpointRule;
use App\Service\RateLimit\GuestRule;
use App\Service\RateLimit\RuleFactory;
use App\Service\RateLimit\UserRule;
use Tests\TestCase;

class RuleFactoryTest extends TestCase
{
    private RuleFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new RuleFactory();
    }

    /** @dataProvider tightenedEndpointsProvider */
    public function testTightenedEndpointsGetAnEndpointSpecificGuestRule(
        string $method,
        string $path,
        int $expectedLimit,
    ): void {
        $rule = $this->factory->getRule(clientIp: '1.2.3.4', method: $method, path: $path);

        $this->assertInstanceOf(GuestEndpointRule::class, $rule);
        $this->assertEquals($expectedLimit, $rule->limit());
        $this->assertStringStartsWith('guest:', $rule->key());
        $this->assertStringEndsWith(':1.2.3.4', $rule->key());
    }

    public function tightenedEndpointsProvider(): array
    {
        return [
            'login' => ['POST', '/auth/login', 15],
            'register' => ['POST', '/auth/register', 3],
            'password forgot' => ['POST', '/auth/password/forgot', 3],
            'password reset' => ['POST', '/auth/password/reset', 5],
        ];
    }

    /**
     * Prefix matches would also tighten these routes, which must stay at the default guest
     * limit — the nick-name check is polled interactively while a user types, and the passkey
     * login path is not a password-guessing target.
     *
     * @dataProvider nonTightenedPathsProvider
     */
    public function testNonTableEndpointsKeepTheDefaultGuestRule(string $method, string $path): void
    {
        $rule = $this->factory->getRule(clientIp: '1.2.3.4', method: $method, path: $path);

        $this->assertInstanceOf(GuestRule::class, $rule);
        $this->assertNotInstanceOf(GuestEndpointRule::class, $rule);
        $this->assertEquals(100, $rule->limit());
        $this->assertEquals('guest:1.2.3.4', $rule->key());
    }

    public function nonTightenedPathsProvider(): array
    {
        return [
            'nick name check' => ['POST', '/auth/register/check/nick-name'],
            'passkey login' => ['POST', '/auth/login/passkey'],
            'google provider' => ['POST', '/auth/provider/google'],
            'wrong method on login path' => ['GET', '/auth/login'],
            'unrelated route' => ['GET', '/wallets'],
        ];
    }

    public function testAuthenticatedUserAlwaysGetsUserRuleRegardlessOfPath(): void
    {
        $rule = $this->factory->getRule(userId: '123', clientIp: '1.2.3.4', method: 'POST', path: '/auth/login');

        $this->assertInstanceOf(UserRule::class, $rule);
        $this->assertEquals('user:123-1.2.3.4', $rule->key());
    }
}
