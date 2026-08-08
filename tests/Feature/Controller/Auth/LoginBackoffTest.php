<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Testing\Http\TestResponse;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

/**
 * P2-4 Part 2: per-email failed-login backoff, driven through real Redis, real wall-clock time,
 * and the real LoginController flow (RateLimitMiddlewareTest / LoginBackoffServiceTest cover the
 * IP-based endpoint limit and the unit-level backoff math respectively).
 *
 * Some tests here use real sleep() calls to let a backoff window actually elapse. That is a test
 * concern only — LoginBackoffService/LoginController never sleep() in the request path, which
 * would block a RoadRunner worker; the delay is expressed purely as Retry-After.
 */
class LoginBackoffTest extends TestCase implements DatabaseTransaction
{
    public const ENV = [
        'REDIS_HOST' => 'localhost',
    ];

    protected UserFactory $userFactory;

    public function setUp(): void
    {
        $this->beforeBooting(static function (ConfiguratorInterface $config): void {
            $config->modify('redis', new Set('prefix', 'CT:testing:'));
        });

        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function tearDown(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $keys = $redis->keys('*');

        if (is_array($keys) && $keys !== []) {
            $redis->del(array_map(
                static fn(string $key): string => substr($key, strlen('CT:testing:')),
                $keys,
            ));
        }

        parent::tearDown();
    }

    private function backoffKey(string $email): string
    {
        return 'login-backoff:' . hash('sha256', $email);
    }

    /**
     * Each call carries a distinct X-Forwarded-For so /auth/login's own IP-based guest limit
     * (see RateLimitMiddlewareTest) never interferes with these per-email backoff assertions —
     * the two mechanisms are independent by design and must be tested that way.
     */
    private function attemptLogin(string $email, string $password): TestResponse
    {
        return $this->post('/auth/login', [
            'email' => $email,
            'password' => $password,
        ], [
            'X-Forwarded-For' => long2ip(Fixtures::integer()),
        ]);
    }

    public function testFailedLoginsIncrementAttemptsAndSuccessResetsState(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $user = $this->userFactory->create();
        $key = $this->backoffKey($user->email);

        $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        $this->assertEquals('1', $redis->hGet($key, 'attempts'));

        $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        $this->assertEquals('2', $redis->hGet($key, 'attempts'));

        $this->attemptLogin($user->email, UserFactory::DEFAULT_PASSWORD)->assertOk();
        $this->assertFalse($redis->hGet($key, 'attempts'));
        $this->assertFalse($redis->hGet($key, 'until'));
    }

    public function testFourthAttemptIsThrottledAndDoesNotCountAsAnAdditionalFailure(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $user = $this->userFactory->create();
        $key = $this->backoffKey($user->email);

        // FREE_ATTEMPTS is 3 — the first 3 failures are not delayed (checked before the
        // increment that follows them); the 4th attempt is throttled before it can fail.
        for ($i = 1; $i <= 3; $i++) {
            $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        }

        $this->assertEquals('3', $redis->hGet($key, 'attempts'));

        $response = $this->attemptLogin($user->email, Fixtures::string());

        $response->assertStatus(429);
        $response->assertHasHeader('Retry-After', '2');

        // The throttled request is rejected before AuthService::login() ever runs, so it must
        // not count as an additional failure.
        $this->assertEquals('3', $redis->hGet($key, 'attempts'));

        // A correct password does not bypass the backoff either — while the window is active,
        // every attempt is rejected before credentials are checked at all.
        $blockedCorrectAttempt = $this->attemptLogin($user->email, UserFactory::DEFAULT_PASSWORD);

        $blockedCorrectAttempt->assertStatus(429);
    }

    /**
     * End-to-end with real sleeps: three free failures, a throttled 4th, waiting out the window,
     * a real 4th failure that grows the delay, waiting that out too, and a correct password that
     * must succeed and fully clear the stored state.
     */
    public function testBackoffWindowElapsesLettingDelayGrowAndACorrectPasswordEndTheStreak(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $user = $this->userFactory->create();
        $key = $this->backoffKey($user->email);

        for ($i = 1; $i <= 3; $i++) {
            $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        }

        $blocked = $this->attemptLogin($user->email, Fixtures::string());
        $blocked->assertStatus(429);
        $blocked->assertHasHeader('Retry-After', '2');

        sleep(3);

        // A real 4th failure: must reach AuthService::login() now that the window has passed.
        $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        $this->assertEquals('4', $redis->hGet($key, 'attempts'));

        // The delay must have grown from 2s to 4s — proof it is not frozen at its first value.
        $blockedAgain = $this->attemptLogin($user->email, Fixtures::string());
        $blockedAgain->assertStatus(429);
        $blockedAgain->assertHasHeader('Retry-After', '4');

        // Wait out the grown window, then submit the correct password.
        sleep(5);

        $this->attemptLogin($user->email, UserFactory::DEFAULT_PASSWORD)->assertOk();
        $this->assertFalse($redis->hGet($key, 'attempts'));
        $this->assertFalse($redis->hGet($key, 'until'));

        // State is fully reset, not just unblocked-but-still-primed: the next wrong password is
        // free again rather than immediately throttled.
        $this->attemptLogin($user->email, Fixtures::string())->assertStatus(400);
        $this->assertEquals('1', $redis->hGet($key, 'attempts'));
    }

    public function testBackoffCapsAtSixtySeconds(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $user = $this->userFactory->create();
        $key = $this->backoffKey($user->email);

        // Seed a stored deadline directly instead of driving 8 real failures (2/4/8/16/32/60s of
        // real sleeps) to reach the cap.
        $redis->hSet($key, 'attempts', '50');
        $redis->hSet($key, 'until', (string) (time() + 60));

        $response = $this->attemptLogin($user->email, UserFactory::DEFAULT_PASSWORD);

        $response->assertStatus(429);
        $response->assertHasHeader('Retry-After', '60');
    }

    public function testDifferentEmailsHaveIndependentCounters(): void
    {
        /** @var \Redis $redis */
        $redis = $this->getContainer()->get(\Redis::class);

        $throttledUser = $this->userFactory->create();
        $otherUser = $this->userFactory->create();

        $redis->hSet($this->backoffKey($throttledUser->email), 'until', (string) (time() + 30));

        $this->attemptLogin($throttledUser->email, UserFactory::DEFAULT_PASSWORD)->assertStatus(429);

        // A failure streak against one email must not throttle logins for another.
        $this->attemptLogin($otherUser->email, UserFactory::DEFAULT_PASSWORD)->assertOk();
    }

    /**
     * Enumeration parity: AuthService::login() returns null uniformly for "wrong password" and
     * "no such user", and the backoff keys off a hash of the submitted email regardless of
     * whether it belongs to a real account. Both must accumulate and throttle identically, so a
     * timing or status-code difference can't be used to probe which emails are registered.
     */
    public function testBackoffBehavesIdenticallyForRealAndNonexistentEmails(): void
    {
        $realUser = $this->userFactory->create();
        $nonexistentEmail = Fixtures::email();

        for ($i = 1; $i <= 3; $i++) {
            $realResponse = $this->attemptLogin($realUser->email, Fixtures::string());
            $fakeResponse = $this->attemptLogin($nonexistentEmail, Fixtures::string());

            $realResponse->assertStatus(400);
            $fakeResponse->assertStatus(400);
            $this->assertSame($realResponse->getJsonParsedBody(), $fakeResponse->getJsonParsedBody());
        }

        $realBlocked = $this->attemptLogin($realUser->email, Fixtures::string());
        $fakeBlocked = $this->attemptLogin($nonexistentEmail, Fixtures::string());

        $realBlocked->assertStatus(429);
        $fakeBlocked->assertStatus(429);
        $realBlocked->assertHasHeader('Retry-After', '2');
        $fakeBlocked->assertHasHeader('Retry-After', '2');
    }
}
