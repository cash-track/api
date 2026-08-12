<?php

declare(strict_types=1);

namespace Tests\Feature\Bootloader;

use App\Config\RedisConfig;
use App\Redis\ReconnectingRedis;
use Psr\Log\LoggerInterface;
use Redis;
use Spiral\Testing\Attribute\Env;
use Tests\DatabaseTransaction;
use Tests\Factories\PasskeyFactory;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\PasskeyServiceMocker;
use Tests\Fixtures;
use Tests\TestCase;

/**
 * REDIS_CONNECTION='' (the default in every other test) short-circuits before ever attempting a
 * connection, so these tests point it at a syntactically valid but unreachable address instead,
 * to prove a Redis outage degrades every consumer gracefully instead of throwing during
 * container resolution and breaking request handling app-wide.
 */
class RedisUnavailableTest extends TestCase implements DatabaseTransaction
{
    use PasskeyServiceMocker;

    // Port 1: nothing listens there, so the OS refuses immediately instead of timing out.
    private const string UNREACHABLE_CONNECTION = '127.0.0.1:1';

    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testRedisSingletonDegradesToDisconnectedClientInsteadOfThrowing(): void
    {
        $redis = $this->getContainer()->get(Redis::class);

        $this->assertInstanceOf(Redis::class, $redis);
        $this->assertFalse($redis->isConnected());
    }

    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testUnauthenticatedJwksEndpointStillRespondsWhenRedisIsUnreachable(): void
    {
        // This route needs no Redis of its own, but AuthMiddleware constructs
        // TokenStorageInterface as global middleware regardless; before the fix this 500'd
        // purely from Redis being down.
        $response = $this->get('/.well-known/jwks.json');

        $response->assertOk();
        $this->assertEquals(['keys' => []], $this->getJsonResponseBody($response));
    }

    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testAuthenticatedRequestFlowStillSucceedsWhenRedisIsUnreachable(): void
    {
        // Exercises the isConnected() === false guard in TokenStorage::load()/delete() against
        // a real, disconnected, bootloader-resolved client (not a mock). The connected-but-
        // throws path is covered separately by TokenStorageTest's mocked try/catch tests.
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->get('/v1/profile');
        $response->assertOk();

        $response = $this->withAuth($auth)->post('/v1/auth/logout');
        $response->assertOk();
    }

    /**
     * Unauthenticated (no Authorization header), so TokenStorage is not in play here —
     * RateLimitMiddleware is this route's only other isConnected() caller. Proves the outage
     * surfaces as a clean 503, and that PasskeyService's own guard degrades and recovers
     * correctly on its own, independent of RateLimitMiddleware.
     */
    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testPasskeyLoginInitFailsCleanlyWhenRedisIsUnreachableThenSucceedsOnceRedisIsHealthyAgain(): void
    {
        $response = $this->get('/v1/auth/login/passkey/init');

        $response->assertStatus(503);
        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        // Simulates Redis recovering: 127.0.0.1:1 can't itself start accepting connections
        // mid-test, so the singleton is swapped for a ReconnectingRedis pointed at the real
        // test Redis instead of waiting out its own cooldown (that self-heal is proven
        // separately, at the unit level, by ReconnectingRedisTest).
        $this->getContainer()->bindSingleton(
            Redis::class,
            new ReconnectingRedis(
                new RedisConfig([
                    'connection' => 'localhost:6379',
                    'timeout' => 1.0,
                    'retry_interval' => 2,
                    'retry_timeout' => 1.0,
                    'prefix' => 'CT:testing:passkey-recovery:',
                    'max_retries' => 1,
                ]),
                $this->getContainer()->get(LoggerInterface::class),
            ),
            force: true,
        );

        $response = $this->get('/v1/auth/login/passkey/init');

        $response->assertOk();
        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('challenge', $body);
        $this->assertNotEmpty($body['challenge']);
    }

    /**
     * Covers PasskeyController::login() -> authenticate() -> getRequestOptions(), which asserts
     * Redis availability before looking at $data, so arbitrary values reach the guard. Also
     * unauthenticated, so TokenStorage is not in play here either.
     */
    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testPasskeyLoginFailsCleanlyWhenRedisIsUnreachable(): void
    {
        $response = $this->post('/v1/auth/login/passkey', [
            'challenge' => Fixtures::string(32),
            'data' => Fixtures::string(32),
        ]);

        $response->assertStatus(503);
        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    /**
     * Covers Profile\PasskeyController::init() -> PasskeyService::init(). This route requires
     * auth, so TokenStorage::load() runs first and fails open (see
     * testAuthenticatedRequestFlowStillSucceedsWhenRedisIsUnreachable()); PasskeyService's own
     * guard then turns the outage into a 503.
     */
    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testProfilePasskeyInitFailsCleanlyWhenRedisIsUnreachable(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/v1/profile/passkey/init', [
            'name' => Fixtures::string(6),
        ]);

        $response->assertStatus(503);
        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    /**
     * Covers Profile\PasskeyController::store() -> PasskeyService::store(). Unlike the other
     * three tests here, $data must be a well-formed WebAuthn creation response, since store()
     * deserializes it before ever touching Redis; makeCreateData() builds that shape.
     */
    #[Env('REDIS_CONNECTION', self::UNREACHABLE_CONNECTION)]
    public function testProfilePasskeyStoreFailsCleanlyWhenRedisIsUnreachable(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();
        $options = $this->makeCreationChallengeOptions($challenge, $user);
        $data = $this->makeCreateData($options, $passkey);

        $response = $this->withAuth($auth)->post('/v1/profile/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(503);
        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }
}
