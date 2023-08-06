<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Repository\UserRepository;
use App\Service\PhotoStorageService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class GoogleProviderControllerTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    protected function googleAccountInfo(): array
    {
        return [
            'token' => Fixtures::string(160),
            'googleId' => (string) Fixtures::integer(10000, 100000),
            'email' => Fixtures::email(),
            'photoUrl' => Fixtures::url(),
        ];
    }

    public function testLoggedInNewUser(): void
    {
        [
            'token' => $token,
            'googleId' => $googleId,
            'email' => $email,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $googleId,
            'email' => $email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => $firstName = Fixtures::string(),
            'family_name' => $lastName = Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $otherUser = UserFactory::make();
        $otherUser->nickName = str_slug("{$firstName} {$lastName}");
        $this->userFactory->create($otherUser);

        $this->mock(PhotoStorageService::class, ['queueDownloadProfilePhoto'], function (MockObject $mock) use ($photoUrl) {
            $mock->expects($this->once())->method('queueDownloadProfilePhoto')->with($this->anything(), $photoUrl, null, null);
        });

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertOk();

        $auth = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $auth);
        $this->assertArrayHasKey('id', $auth['data']);
        $this->assertArrayHasKey('accessToken', $auth);
        $this->assertArrayHasKey('refreshToken', $auth);

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);

        $this->assertDatabaseHas('google_accounts', [
            'account_id' => $googleId,
            'user_id' => $body['data']['id'],
        ]);
    }

    public function testLoggedInExistingUser(): void
    {
        [
            'token' => $token,
            'googleId' => $googleId,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $user = UserFactory::make();
        $user->photo = null;
        $email = $user->email;
        $user = $this->userFactory->create($user);

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $googleId,
            'email' => $email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $this->mock(PhotoStorageService::class, ['queueDownloadProfilePhoto', 'getProfilePhotoPublicUrl'], function (MockObject $mock) use ($photoUrl) {
            $mock->method('getProfilePhotoPublicUrl')->willReturn(Fixtures::url());
            $mock->expects($this->once())->method('queueDownloadProfilePhoto')->with($this->anything(), $photoUrl, null, null);
        });

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertOk();

        $auth = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $auth);
        $this->assertArrayHasKey('id', $auth['data']);
        $this->assertArrayHasKey('accessToken', $auth);
        $this->assertArrayHasKey('refreshToken', $auth);

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertEquals($user->id, $body['data']['id']);

        $this->assertDatabaseHas('google_accounts', [
            'account_id' => $googleId,
            'user_id' => $body['data']['id'],
        ]);
    }

    public function testLoggedInExistingUserExistingGoogleAccount(): void
    {
        $user = UserFactory::make();

        [
            'token' => $token,
            'googleId' => $googleId,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $existingData = [
            'sub' => $googleId,
            'email' => $user->email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ];

        $user = $this->userFactory->create(UserFactory::withGoogleAccount($existingData, $user));

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $googleId,
            'email' => $user->email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $this->mock(PhotoStorageService::class, ['queueDownloadProfilePhoto', 'getProfilePhotoPublicUrl'], function (MockObject $mock) use ($photoUrl) {
            $mock->method('getProfilePhotoPublicUrl')->willReturn(Fixtures::url());
        });

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertOk();

        $auth = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $auth);
        $this->assertArrayHasKey('id', $auth['data']);
        $this->assertArrayHasKey('accessToken', $auth);
        $this->assertArrayHasKey('refreshToken', $auth);

        $response = $this->withAuth($auth)->get('/profile');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertEquals($user->id, $body['data']['id']);

        $this->assertDatabaseHas('google_accounts', [
            'account_id' => $googleId,
            'user_id' => $body['data']['id'],
        ]);
    }

    public function testFailedExistingUserAlreadyHaveDifferentGoogleAccount(): void
    {
        $user = UserFactory::make();

        [
            'googleId' => $googleId,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $existingData = [
            'sub' => $googleId,
            'email' => $user->email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ];

        $user = $this->userFactory->create(UserFactory::withGoogleAccount($existingData, $user));

        [
            'token' => $token,
            'googleId' => $differentGoogleId,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $differentGoogleId,
            'email' => $user->email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $this->mock(PhotoStorageService::class, ['queueDownloadProfilePhoto', 'getProfilePhotoPublicUrl'], function (MockObject $mock) use ($photoUrl) {
            $mock->method('getProfilePhotoPublicUrl')->willReturn(Fixtures::url());
        });

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseHas('google_accounts', [
            'account_id' => $googleId,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('google_accounts', [
            'account_id' => $differentGoogleId,
            'user_id' => $user->id,
        ]);
    }

    public function testFailedUnconfirmedGoogleAccountEmail(): void
    {
        $user = $this->userFactory->create();

        [
            'token' => $token,
            'googleId' => $googleId,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $googleId,
            'email' => $user->email,
            'email_verified' => false,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);

        $this->assertDatabaseMissing('google_accounts', [
            'account_id' => $googleId,
            'user_id' => $user->id,
        ]);
    }

    public function testTokenVerificationFailed(): void
    {
        $message = Fixtures::string();
        ['token' => $token, 'googleId' => $googleId] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)
                             ->onlyMethods(['verifyIdToken'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $googleClient->expects($this->once())
                     ->method('verifyIdToken')
                     ->with($token)
                     ->willThrowException(new \RuntimeException($message));
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertEquals($message, $body['error']);

        $this->assertDatabaseMissing('google_accounts', [
            'account_id' => $googleId,
        ]);
    }

    public function tokenInvalidDataProvider(): array
    {
        return [
            [false],
            [[]],
            [['sub' => null, 'email' => null, 'picture' => null, 'given_name' => null, 'family_name' => null,]],
            [['sub' => '', 'email' => '', 'picture' => '', 'given_name' => '', 'family_name' => '',]],
            [['sub' => '123', 'email' => '', 'picture' => '123', 'given_name' => '123', 'family_name' => '123',]],
        ];
    }

    /**
     * @dataProvider tokenInvalidDataProvider
     * @param bool|array $data
     * @return void
     */
    public function testTokenInvalid(bool|array $data): void
    {
        ['token' => $token] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)
                             ->onlyMethods(['verifyIdToken'])
                             ->disableOriginalConstructor()
                             ->getMock();
        $googleClient->expects($this->once())
                     ->method('verifyIdToken')
                     ->with($token)
                     ->willReturn($data);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
    }

    public function testFindUserError(): void
    {
        $message = Fixtures::string();
        [
            'token' => $token,
            'googleId' => $googleId,
            'email' => $email,
            'photoUrl' => $photoUrl,
        ] = $this->googleAccountInfo();

        $googleClient = $this->getMockBuilder(\Google\Client::class)->onlyMethods(['verifyIdToken'])->disableOriginalConstructor()->getMock();
        $googleClient->expects($this->once())->method('verifyIdToken')->with($token)->willReturn([
            'sub' => $googleId,
            'email' => $email,
            'email_verified' => true,
            'picture' => $photoUrl,
            'given_name' => Fixtures::string(),
            'family_name' => Fixtures::string(),
        ]);
        $this->getContainer()->bind(\Google\Client::class, fn() => $googleClient);

        $this->mock(UserRepository::class, ['findByEmail'], function (MockObject $mock) use ($email, $message) {
            $mock->method('findByEmail')->with($email)->willThrowException(new \RuntimeException($message));
        });

        $response = $this->post('/auth/provider/google', [
            'token' => $token,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('error', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertEquals($message, $body['error']);
    }
}
