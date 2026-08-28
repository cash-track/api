<?php

declare(strict_types=1);

namespace Tests\Feature\Metrics;

use App\Service\Auth\Passkey\Exception\PasskeyNotFoundException;
use App\Service\Auth\Passkey\PasskeyService;
use App\Service\Metrics\AppMetricsInterface;
use App\Service\PhotoStorageService;
use App\Service\UserService;
use Google\Client as GoogleClient;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\GoogleAccountFactory;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

/**
 * Wiring checks: the auth controllers must feed app_auth_logins_total / app_auth_registrations_total
 * on both the success and failure branches, for every login method (password, google, passkey).
 */
final class AuthMetricsTest extends TestCase implements DatabaseTransaction
{
    private UserFactory $userFactory;
    private GoogleAccountFactory $googleAccountFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->googleAccountFactory = $this->getContainer()->get(GoogleAccountFactory::class);
    }

    public function testSuccessfulPasswordLoginRecordsSuccess(): void
    {
        $user = $this->userFactory->create();

        $this->expectLoginMetric('password', true);

        $this->post('/v1/auth/login', [
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
        ])->assertOk();
    }

    public function testFailedPasswordLoginRecordsFailure(): void
    {
        $user = $this->userFactory->create();

        $this->expectLoginMetric('password', false);

        $this->post('/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(400);
    }

    public function testRegistrationSuccessRecordsSuccess(): void
    {
        $user = UserFactory::make();

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementRegistration')->with(true);

        $this->post('/v1/auth/register', [
            'name' => $user->name,
            'nickName' => $user->nickName,
            'email' => $user->email,
            'password' => UserFactory::DEFAULT_PASSWORD,
            'passwordConfirmation' => UserFactory::DEFAULT_PASSWORD,
            'locale' => UserFactory::locale(),
        ])->assertOk();
    }

    public function testRegistrationValidationFailureIsNotCountedAsAttempt(): void
    {
        $mock = $this->mockMetrics();
        $mock->expects($this->never())->method('incrementRegistration');

        // Missing password confirmation -> request validation fails before the controller body.
        $this->post('/v1/auth/register', [
            'name' => 'A',
            'nickName' => 'x',
            'email' => 'not-an-email',
        ])->assertUnprocessable();
    }

    public function testSuccessfulGoogleLoginRecordsSuccess(): void
    {
        $user = UserFactory::make();
        $data = $this->googleTokenData($user->email);
        $user = $this->userFactory->create($user);
        $this->googleAccountFactory->create(GoogleAccountFactory::withUser($user, $data));

        $this->stubGoogleClient('token', $data);

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementLogin')->with('google', true);
        $mock->expects($this->never())->method('incrementRegistration');

        $this->post('/v1/auth/provider/google', ['token' => 'token'])->assertOk();
    }

    public function testFailedGoogleLoginRecordsFailure(): void
    {
        $this->stubGoogleClient('token', false);

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementLogin')->with('google', false);

        $this->post('/v1/auth/provider/google', ['token' => 'token'])->assertStatus(400);
    }

    public function testSuccessfulGoogleRegistrationRecordsBothMetrics(): void
    {
        $data = $this->googleTokenData(Fixtures::email());

        $this->stubGoogleClient('token', $data);
        $this->mock(PhotoStorageService::class, ['queueDownloadProfilePhoto'], static function (MockObject $mock): void {
            $mock->method('queueDownloadProfilePhoto');
        });

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementLogin')->with('google', true);
        $mock->expects($this->once())->method('incrementRegistration')->with(true);

        $this->post('/v1/auth/provider/google', ['token' => 'token'])->assertOk();
    }

    public function testFailedGoogleRegistrationRecordsFailure(): void
    {
        $data = $this->googleTokenData(Fixtures::email());

        $this->stubGoogleClient('token', $data);
        $this->mock(UserService::class, ['store'], static function (MockObject $mock): void {
            $mock->method('store')->willThrowException(new \RuntimeException('db down'));
        });

        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementRegistration')->with(false);
        $mock->expects($this->once())->method('incrementLogin')->with('google', false);

        $this->post('/v1/auth/provider/google', ['token' => 'token'])->assertStatus(500);
    }

    public function testSuccessfulPasskeyLoginRecordsSuccess(): void
    {
        $user = $this->userFactory->create();

        $this->mock(PasskeyService::class, ['authenticate'], static function (MockObject $mock) use ($user): void {
            $mock->method('authenticate')->willReturn($user);
        });

        $this->expectLoginMetric('passkey', true);

        $this->post('/v1/auth/login/passkey', [
            'challenge' => Fixtures::string(),
            'data' => Fixtures::string(),
        ])->assertOk();
    }

    public function testFailedPasskeyLoginRecordsFailure(): void
    {
        $this->mock(PasskeyService::class, ['authenticate'], static function (MockObject $mock): void {
            $mock->method('authenticate')->willThrowException(new PasskeyNotFoundException('no passkey'));
        });

        $this->expectLoginMetric('passkey', false);

        $this->post('/v1/auth/login/passkey', [
            'challenge' => Fixtures::string(),
            'data' => Fixtures::string(),
        ])->assertStatus(400);
    }

    /**
     * @return array{sub: string, email: string, email_verified: true, picture: string, given_name: string, family_name: string}
     */
    private function googleTokenData(string $email): array
    {
        return [
            'sub' => (string) Fixtures::integer(10000, 100000),
            'email' => $email,
            'email_verified' => true,
            'picture' => Fixtures::url(),
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ];
    }

    private function stubGoogleClient(string $token, array|bool $data): void
    {
        $client = $this->getMockBuilder(GoogleClient::class)
            ->onlyMethods(['verifyIdToken'])
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('verifyIdToken')->with($token)->willReturn($data);

        $this->getContainer()->bind(GoogleClient::class, static fn () => $client);
    }

    private function expectLoginMetric(string $method, bool $success): void
    {
        $mock = $this->mockMetrics();
        $mock->expects($this->once())->method('incrementLogin')->with($method, $success);
    }

    /**
     * @return AppMetricsInterface&MockObject
     */
    private function mockMetrics(): AppMetricsInterface&MockObject
    {
        $mock = $this->getMockBuilder(AppMetricsInterface::class)->getMock();

        $this->getContainer()->bind(AppMetricsInterface::class, static fn () => $mock);

        return $mock;
    }
}
