<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use Redis;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Testing\Attribute\Env;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class LogoutControllerTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    // Dedicated prefix so this file's guest-bucket increments don't collide with
    // RateLimitMiddlewareTest's exact-count assertions under parallel test runs.
    private const REDIS_PREFIX = 'CT:testing:logout:';

    protected function setUp(): void
    {
        $this->beforeBooting(static function (ConfiguratorInterface $config): void {
            $config->modify('redis', new Set('prefix', self::REDIS_PREFIX));
        });

        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    protected function tearDown(): void
    {
        /** @var Redis $redis */
        $redis = $this->getContainer()->get(Redis::class);

        if ($redis->isConnected()) {
            $keys = $redis->keys('*');

            if (is_array($keys) && $keys !== []) {
                // del() re-applies OPT_PREFIX, so it must be stripped from keys() results first.
                $redis->del(array_map(
                    static fn(string $key): string => substr($key, strlen(self::REDIS_PREFIX)),
                    $keys,
                ));
            }
        }

        parent::tearDown();
    }

    public function testWithoutAuth(): void
    {
        $response = $this->post('/auth/logout');

        $response->assertUnauthorized();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testLoggedOut(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/auth/logout');

        $response->assertOk();

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertUnauthorized();
    }

    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testLoggedOutClosesRefreshToken(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/auth/logout', [
            'refreshToken' => $auth['refreshToken'],
        ]);

        $response->assertOk();

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertUnauthorized();

        $response = $this->post('/auth/refresh', ['refreshToken' => $auth['refreshToken']]);

        $response->assertUnauthorized();
    }

    public function testLoggedOutWithoutRealRedisDoesNotBreakLogout(): void
    {
        // REDIS_CONNECTION defaults to '' in tests: proves logout succeeds even when
        // revocation can't be recorded.
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/auth/logout');

        $response->assertOk();
    }
}
