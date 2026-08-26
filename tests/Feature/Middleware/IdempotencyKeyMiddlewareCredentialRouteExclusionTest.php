<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Service\Idempotency\IdempotencyClaim;
use App\Service\Idempotency\IdempotencyStoreInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * Credential-issuing auth routes must never be claimed: their 200 body carries a live token
 * pair, which caching would persist in Redis for 24h. See
 * IdempotencyKeyMiddleware::CREDENTIAL_ISSUING_ROUTES.
 *
 * Each test drives the real prefixed route through the full pipeline with a mocked store and
 * asserts claim() is never called. URLs are literal strings on purpose: deriving them from the
 * request path or the middleware's own constant would agree with a broken exclusion.
 */
class IdempotencyKeyMiddlewareCredentialRouteExclusionTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    private function expectStoreNeverTouched(): void
    {
        $this->mock(IdempotencyStoreInterface::class, [], function (MockObject $mock): void {
            $mock->expects($this->never())->method('claim');
            $mock->expects($this->never())->method('complete');
            $mock->expects($this->never())->method('release');
        });
    }

    public function testLoginIsExcludedFromIdempotency(): void
    {
        $this->expectStoreNeverTouched();

        $user = $this->userFactory->create();

        $response = $this->post(
            '/v1/auth/login',
            ['email' => $user->email, 'password' => UserFactory::DEFAULT_PASSWORD],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);
        $response->assertHeaderMissing('Idempotency-Replayed');
    }

    public function testRegisterIsExcludedFromIdempotency(): void
    {
        $this->expectStoreNeverTouched();

        $user = UserFactory::make();

        $response = $this->post(
            '/v1/auth/register',
            [
                'name' => $user->name,
                'nickName' => $user->nickName,
                'email' => $user->email,
                'password' => UserFactory::DEFAULT_PASSWORD,
                'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
                'locale' => UserFactory::locale(),
            ],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);
    }

    public function testRefreshIsExcludedFromIdempotency(): void
    {
        $this->expectStoreNeverTouched();

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->post(
            '/v1/auth/refresh',
            ['refreshToken' => $auth['refreshToken']],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);
        $this->assertArrayHasKey('accessToken', $body);
        $this->assertArrayHasKey('refreshToken', $body);
        // Rotation is the entire point of /auth/refresh — proves a second call with the same
        // key genuinely rotates again rather than the store having silently intervened.
        $this->assertNotEquals($auth['accessToken'], $body['accessToken']);
        $this->assertNotEquals($auth['refreshToken'], $body['refreshToken']);
    }

    public function testGoogleProviderIsExcludedFromIdempotencyEvenOnFailure(): void
    {
        $this->expectStoreNeverTouched();

        $googleClient = $this->getMockBuilder(\Google\Client::class)
            ->onlyMethods(['verifyIdToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $googleClient->expects($this->once())
            ->method('verifyIdToken')
            ->willThrowException(new \RuntimeException('invalid token'));
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        // Failure path (400), not the token-issuing 200 path: proves the exclusion is keyed on
        // the matched route, not on the response actually carrying tokens.
        $response = $this->post(
            '/v1/auth/provider/google',
            ['token' => 'not-a-real-token'],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $response->assertStatus(400);
    }

    public function testPasskeyLoginIsExcludedFromIdempotencyEvenOnValidationFailure(): void
    {
        $this->expectStoreNeverTouched();

        // Empty challenge/data fails validation (422) before the controller runs, proving the
        // exclusion is applied by the middleware itself rather than downstream.
        $response = $this->post(
            '/v1/auth/login/passkey',
            ['challenge' => '', 'data' => ''],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $response->assertUnprocessable();
    }

    /**
     * Negative control: the email-amplification routes are not credential-issuing and must keep
     * claiming, so the class can't pass vacuously if the exclusion over-matches.
     */
    public function testForgotPasswordIsNotExcludedAndStillClaims(): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $this->getContainer()->bind(IdempotencyStoreInterface::class, fn() => $store);

        $response = $this->post(
            '/v1/auth/password/forgot',
            ['email' => 'nobody@example.com'],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $this->assertNotSame(404, $response->getStatusCode());
    }

    public function testEmailConfirmationResendIsNotExcludedAndStillClaims(): void
    {
        $store = $this->createMock(IdempotencyStoreInterface::class);
        $store->expects($this->once())->method('claim')->willReturn(IdempotencyClaim::claimed());
        $this->getContainer()->bind(IdempotencyStoreInterface::class, fn() => $store);

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post(
            '/v1/auth/email/confirmation/resend',
            [],
            ['Idempotency-Key' => Uuid::uuid4()->toString()],
        );

        $this->assertNotSame(404, $response->getStatusCode());
    }
}
