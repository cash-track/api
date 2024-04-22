<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

use App\Config\PasskeyConfig;
use App\Repository\UserRepository;
use App\Service\Auth\AuthService;
use App\Service\Auth\Passkey\PasskeyService;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\PasskeyFactory;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\PasskeyServiceMocker;
use Tests\Fixtures;
use Tests\TestCase;

class PasskeyControllerTest extends TestCase implements DatabaseTransaction
{
    use PasskeyServiceMocker;

    protected UserFactory $userFactory;

    protected PasskeyFactory $passkeyFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->passkeyFactory = $this->getContainer()->get(PasskeyFactory::class);
    }

    public function testInit(): void
    {
        $challenge = Fixtures::string(32);

        /** @var PasskeyConfig $config */
        $config = $this->getContainer()->get(PasskeyConfig::class);

        $passkeyAuthService = $this->makePasskeyAuthMock(
            ['generateChallenge'],
            function (\Redis|MockObject $redis) use ($challenge) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hMSet')
                      ->with(
                          $key,
                          $this->callback(function(array $data) use ($challenge) {
                              $this->assertIsArray($data);
                              $this->assertArrayHasKey('challenge', $data);
                              $this->assertArrayHasKey('options', $data);
                              $this->assertEquals($challenge, $data['challenge']);
                              $this->assertIsArray(json_decode($data['options'], true));
                              return true;
                          })
                      )
                      ->willReturn(true);
                $redis->expects($this->once())
                      ->method('expire')
                      ->with($key, PasskeyService::CHALLENGE_TTL_SEC)
                      ->willReturn(true);
            },
        );

        $passkeyAuthService->expects($this->once())
                           ->method('generateChallenge')
                           ->willReturn($challenge);

        $response = $this->get('/auth/login/passkey/init');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('challenge', $body);
        $this->assertEquals($challenge, $body['challenge']);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);

        $decoded = json_decode(base64_decode($body['data']), true);

        $this->assertArrayHasKey('challenge', $decoded);
        $this->assertNotEmpty($decoded['challenge']);
        $this->assertEquals($challenge, $decoded['challenge']);

        $this->assertArrayHasKey('rpId', $decoded);
        $this->assertEquals($config->getServiceId(), $decoded['rpId']);

        $this->assertArrayHasKey('userVerification', $decoded);
        $this->assertEquals('required', $decoded['userVerification']);

        $this->assertArrayHasKey('extensions', $decoded);
        $this->assertIsArray($decoded['extensions']);
        $this->assertArrayHasKey('credProps', $decoded['extensions']);
        $this->assertTrue($decoded['extensions']['credProps']);

        $this->assertArrayHasKey('timeout', $decoded);
        $this->assertEquals($config->getTimeout(), $decoded['timeout']);
    }

    public function testInitException(): void
    {
        $serviceMock = $this->getMockBuilder(PasskeyService::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['initAuth'])
                            ->getMock();

        $serviceMock->expects($this->once())
                    ->method('initAuth')
                    ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(PasskeyService::class, fn() => $serviceMock);

        $response = $this->get('/auth/login/passkey/init');

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginValidationFails(): void
    {
        $response = $this->post('/auth/login/passkey', [
            'challenge' => '',
            'data' => '',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach (['challenge', 'data'] as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testLogin(): void
    {
        $passkey = $this->makePasskeyWithData();
        $passkey->usedAt = null;
        $user = $this->userFactory->create();

        $passkey = $this->passkeyFactory->forUser($user)->create($passkey);

        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];
        $data = $this->makeRequestData();

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key)
                      ->willReturn([
                          'challenge' => $challenge,
                          'options' => json_encode($options),
                      ]);
                $redis->expects($this->once())
                      ->method('del')
                      ->with($key)
                      ->willReturn(1);
            },
        );

        $passkeyData = $this->queryDatabase(table: 'passkeys', whereEncrypted: ['key_id' => $passkey->keyId])[0] ?? [];
        $this->assertNull($passkeyData['used_at'] ?? null);

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge,
            'data' => $data,
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

        $passkeyData = $this->queryDatabase(table: 'passkeys', whereEncrypted: ['key_id' => $passkey->keyId])[0] ?? [];
        $this->assertNotNull($passkeyData['used_at'] ?? null);
    }

    public function testLoginInvalidChallenge(): void
    {
        $passkey = $this->makePasskeyWithData();
        $user = $this->userFactory->create();

        $this->passkeyFactory->forUser($user)->create($passkey);

        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];
        $data = $this->makeRequestData();

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key.'1')
                      ->willReturn(false);
            },
        );

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge.'1',
            'data' => $data,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginInvalidClientResponse(): void
    {
        $passkey = $this->makePasskeyWithData();
        $user = $this->userFactory->create();

        $this->passkeyFactory->forUser($user)->create($passkey);

        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key)
                      ->willReturn([
                          'challenge' => $challenge,
                          'options' => json_encode($options),
                      ]);
            },
        );

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge,
            'data' => Fixtures::string(50),
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginPasskeyNotFound(): void
    {
        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];
        $data = $this->makeRequestData();

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key)
                      ->willReturn([
                          'challenge' => $challenge,
                          'options' => json_encode($options),
                      ]);
            },
        );

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginUserNotFound(): void
    {
        $passkey = $this->makePasskeyWithData();
        $user = $this->userFactory->create();

        $this->passkeyFactory->forUser($user)->create($passkey);

        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];
        $data = $this->makeRequestData();

        $userRepositoryMock = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->onlyMethods(['findByPK'])->getMock();
        $userRepositoryMock->expects($this->once())->method('findByPK')->with($user->id)->willReturn(null);
        $this->getContainer()->bind(UserRepository::class, fn() => $userRepositoryMock);

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key)
                      ->willReturn([
                          'challenge' => $challenge,
                          'options' => json_encode($options),
                      ]);
            },
        );

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginWebauthnException(): void
    {
        $passkey = $this->makePasskeyWithData();
        $user = $this->userFactory->create();

        $this->passkeyFactory->forUser($user)->create($passkey);

        $options = $this->makeRequestChallengeOptions();
        $challenge = $options['challenge'];
        $data = $this->makeRequestData();

        $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $modified = $options;
                $modified['challenge'] = Base64UrlSafe::encodeUnpadded($modified['challenge']);
                $redis->expects($this->once())
                      ->method('hGetAll')
                      ->with($key)
                      ->willReturn([
                          'challenge' => $challenge,
                          'options' => json_encode($modified),
                      ]);
            },
        );

        $response = $this->post('/auth/login/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(400);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginException(): void
    {
        $serviceMock = $this->getMockBuilder(PasskeyService::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['authenticate'])
                            ->getMock();

        $serviceMock->expects($this->once())
                    ->method('authenticate')
                    ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(PasskeyService::class, fn() => $serviceMock);

        $response = $this->post('/auth/login/passkey', [
            'challenge' => Fixtures::string(),
            'data' => Fixtures::string(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testLoginAuthException(): void
    {
        $user = $this->userFactory->create();

        $serviceMock = $this->getMockBuilder(PasskeyService::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['authenticate'])
                            ->getMock();

        $serviceMock->expects($this->once())
                    ->method('authenticate')
                    ->willReturn($user);

        $this->getContainer()->bind(PasskeyService::class, fn() => $serviceMock);

        $authMock = $this->getMockBuilder(AuthService::class)
                         ->disableOriginalConstructor()
                         ->onlyMethods(['authenticate'])
                         ->getMock();

        $authMock->expects($this->once())
                 ->method('authenticate')
                 ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(AuthService::class, fn() => $authMock);

        $response = $this->post('/auth/login/passkey', [
            'challenge' => Fixtures::string(),
            'data' => Fixtures::string(),
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }
}
