<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Service\Auth\EmailConfirmationService;
use App\Service\Mailer\MailerInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\ForgotPasswordRequestFactory;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

/**
 * RuleFactory's per-endpoint guest limits, proven through real HTTP requests against the actual
 * routes (unit-level checks: RuleFactoryTest / GuestEndpointRuleTest). Payloads pass Filter
 * validation so the response goes through RateLimitMiddleware's header-adding code.
 *
 * No real Redis needed: RateLimitHit::getLimit() reflects the Rule's configured limit regardless
 * of whether the counter is live (see RateLimitMiddlewareTest for the real-Redis version).
 */
class EndpointRateLimitTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected ForgotPasswordRequestFactory $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->requestFactory = $this->getContainer()->get(ForgotPasswordRequestFactory::class);
    }

    public function testLoginIsTightenedToFifteenPerMinute(): void
    {
        $response = $this->post('/auth/login', [
            'email' => Fixtures::email(),
            'password' => Fixtures::string(),
        ]);

        $response->assertStatus(400);
        $response->assertHasHeader('X-RateLimit-Limit', '15');
    }

    public function testRegisterIsTightenedToThreePerMinute(): void
    {
        $emailService = $this->getMockBuilder(EmailConfirmationService::class)
                             ->disableOriginalConstructor()
                             ->onlyMethods(['create'])
                             ->getMock();

        $this->getContainer()->bind(EmailConfirmationService::class, fn () => $emailService);

        $user = UserFactory::make();

        $response = $this->post('/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
            'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
            'locale' => UserFactory::locale(),
        ]);

        $response->assertOk();
        $response->assertHasHeader('X-RateLimit-Limit', '3');
    }

    public function testPasswordForgotIsTightenedToThreePerMinute(): void
    {
        $mailer = $this->getMockBuilder(MailerInterface::class)->disableOriginalConstructor()->getMock();

        $this->getContainer()->bind(MailerInterface::class, fn () => $mailer);

        $user = $this->userFactory->create();

        $response = $this->post('/auth/password/forgot', [
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertHasHeader('X-RateLimit-Limit', '3');
    }

    public function testPasswordResetIsTightenedToFivePerMinute(): void
    {
        $user = $this->userFactory->create();

        $forgotPasswordRequest = ForgotPasswordRequestFactory::throttled();
        $forgotPasswordRequest->email = $user->email;
        $this->requestFactory->create($forgotPasswordRequest);

        $password = Fixtures::string();

        $response = $this->post('/auth/password/reset', [
            'code' => $forgotPasswordRequest->code,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);

        $response->assertOk();
        $response->assertHasHeader('X-RateLimit-Limit', '5');
    }

    /**
     * A prefix match on /auth/register would also tighten this nick-name check, which is polled
     * interactively while a user types and is not a password-guessing target.
     */
    public function testRegisterCheckNickNameStaysAtDefaultGuestLimit(): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => Fixtures::string(),
        ]);

        $response->assertOk();
        $response->assertHasHeader('X-RateLimit-Limit', '100');
    }

    /**
     * A prefix match on /auth/login would also tighten this passkey callback, which is not a
     * password-guessing target. Redis is unavailable in this test env so PasskeyService 503s —
     * status doesn't matter here, only that the limit stayed untightened.
     */
    public function testLoginPasskeyStaysAtDefaultGuestLimit(): void
    {
        $response = $this->post('/auth/login/passkey', [
            'challenge' => Fixtures::string(),
            'data' => Fixtures::string(),
        ]);

        $response->assertStatus(503);
        $response->assertHasHeader('X-RateLimit-Limit', '100');
    }

    /** /auth/provider/google is a login path outside the tightened-endpoint table by design. */
    public function testProviderGoogleStaysAtDefaultGuestLimit(): void
    {
        $response = $this->post('/auth/provider/google', [
            'token' => Fixtures::string(),
        ]);

        $response->assertHasHeader('X-RateLimit-Limit', '100');
    }
}
