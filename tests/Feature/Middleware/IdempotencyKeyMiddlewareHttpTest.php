<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Ramsey\Uuid\Uuid;
use Redis;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Config\Patch\Set;
use Spiral\Testing\Attribute\Env;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

/**
 * End-to-end wiring through the real HTTP pipeline and real Redis: group registration, the real
 * RedisIdempotencyStore, and the acceptance case that a retried charge doesn't double the
 * wallet balance or create a second Charge row. Branch coverage lives in the Unit test.
 *
 * Follows the real-Redis pattern from LogoutControllerTest: dedicated key prefix set before the
 * container boots, manual cleanup in tearDown(). Only methods carrying #[Env('REDIS_CONNECTION')]
 * talk to Redis.
 */
class IdempotencyKeyMiddlewareHttpTest extends TestCase implements DatabaseTransaction
{
    private const string REDIS_PREFIX = 'CT:testing:idempotency-http:';

    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected function setUp(): void
    {
        $this->beforeBooting(static function (ConfiguratorInterface $config): void {
            $config->modify('redis', new Set('prefix', self::REDIS_PREFIX));
        });

        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
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

    /**
     * Headline acceptance case: the same mutating request sent twice with the same
     * Idempotency-Key creates exactly one Charge row and moves the wallet balance exactly once.
     */
    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testDuplicateChargeCreationRequestIsAppliedExactlyOnce(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $key = Uuid::uuid4()->toString();
        $payload = [
            'type' => '-',
            'amount' => 12.34,
            'title' => 'Groceries',
            'description' => '',
        ];

        $first = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges",
            $payload,
            ['Idempotency-Key' => $key],
        );

        $first->assertOk();
        $first->assertHeaderMissing('Idempotency-Replayed');

        $firstBody = $this->getJsonResponseBody($first);
        $chargeId = $firstBody['data']['id'];

        $second = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges",
            $payload,
            ['Idempotency-Key' => $key],
        );

        $second->assertOk();
        $second->assertHasHeader('Idempotency-Replayed', 'true');

        $secondBody = $this->getJsonResponseBody($second);
        $this->assertSame($chargeId, $secondBody['data']['id']);
        $this->assertSame($firstBody, $secondBody);

        $this->assertDatabaseCount(1, 'charges', ['wallet_id' => $wallet->id]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'total_amount' => -12.34,
        ]);
    }

    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testMalformedIdempotencyKeyReturns400OverRealHttp(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges",
            ['type' => '-', 'amount' => 1, 'title' => 't', 'description' => ''],
            ['Idempotency-Key' => 'not-a-uuid'],
        );

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayNotHasKey('errors', $body);

        $this->assertDatabaseCount(0, 'charges', ['wallet_id' => $wallet->id]);
    }

    /**
     * Same key, different body. The 422 must serialise as an Error (bare `message`), not a
     * ValidationError, so clients can tell it from a field-validation 422 by `errors`.
     */
    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testReusingKeyWithDifferentBodyReturns422ErrorShapedNotValidationErrorShaped(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $key = Uuid::uuid4()->toString();

        $first = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges",
            ['type' => '-', 'amount' => 1, 'title' => 'first', 'description' => ''],
            ['Idempotency-Key' => $key],
        );
        $first->assertOk();

        $second = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges",
            ['type' => '-', 'amount' => 2, 'title' => 'second', 'description' => ''],
            ['Idempotency-Key' => $key],
        );

        $second->assertStatus(422);

        $body = $this->getJsonResponseBody($second);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayNotHasKey('errors', $body);

        $this->assertDatabaseCount(1, 'charges', ['wallet_id' => $wallet->id]);
    }

    /**
     * Same key, method, path and body, but a different query string. Must be treated as two
     * genuinely distinct requests, not a replay of one another.
     */
    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testSameKeyWithDifferingQueryStringExecutesBothRequestsInsteadOfReplaying(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $key = Uuid::uuid4()->toString();
        $payload = ['type' => '-', 'amount' => 1, 'title' => 't', 'description' => ''];

        $first = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges?source=web",
            $payload,
            ['Idempotency-Key' => $key],
        );
        $first->assertOk();
        $first->assertHeaderMissing('Idempotency-Replayed');

        $second = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges?source=mobile",
            $payload,
            ['Idempotency-Key' => $key],
        );
        $second->assertOk();
        $second->assertHeaderMissing('Idempotency-Replayed');

        // Two genuinely distinct requests (query string differs) both really ran.
        $this->assertDatabaseCount(2, 'charges', ['wallet_id' => $wallet->id]);
    }

    /**
     * Same key, method, path, body AND query string — the ordinary replay case, just with a
     * non-empty query string present, proving it doesn't break the identical-repeat path.
     */
    #[Env('REDIS_CONNECTION', 'localhost:6379')]
    public function testSameKeyWithIdenticalQueryStringStillReplays(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $key = Uuid::uuid4()->toString();
        $payload = ['type' => '-', 'amount' => 1, 'title' => 't', 'description' => ''];

        $first = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges?source=web",
            $payload,
            ['Idempotency-Key' => $key],
        );
        $first->assertOk();

        $second = $this->withAuth($auth)->post(
            "/v1/wallets/{$wallet->id}/charges?source=web",
            $payload,
            ['Idempotency-Key' => $key],
        );
        $second->assertOk();
        $second->assertHasHeader('Idempotency-Replayed', 'true');

        $this->assertDatabaseCount(1, 'charges', ['wallet_id' => $wallet->id]);
    }

    /**
     * With the store unreachable, a keyed mutating request still succeeds and is simply not
     * deduplicated — the fail-open contract.
     */
    public function testWithoutRealRedisRequestsStillSucceedAndAreNotDeduplicated(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $wallet = $this->walletFactory->forUser($user)->create();

        $key = Uuid::uuid4()->toString();
        $payload = ['type' => '-', 'amount' => 1, 'title' => 't', 'description' => ''];

        $first = $this->withAuth($auth)->post("/v1/wallets/{$wallet->id}/charges", $payload, ['Idempotency-Key' => $key]);
        $second = $this->withAuth($auth)->post("/v1/wallets/{$wallet->id}/charges", $payload, ['Idempotency-Key' => $key]);

        $first->assertOk();
        $second->assertOk();
        $second->assertHeaderMissing('Idempotency-Replayed');

        // No dedup possible without Redis: both requests really did create a charge.
        $this->assertDatabaseCount(2, 'charges', ['wallet_id' => $wallet->id]);
    }

    public function testSafeMethodIsNeverDeduplicated(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $this->walletFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->get('/v1/wallets', [], ['Idempotency-Key' => 'not-a-uuid-but-ignored']);

        $response->assertOk();
    }
}
