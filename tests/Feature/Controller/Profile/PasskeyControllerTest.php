<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Profile;

use App\Config\PasskeyConfig;
use App\Service\Auth\Passkey\PasskeyService;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\PasskeyFactory;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\AuthAsserts;
use Tests\Feature\Controller\PasskeyServiceMocker;
use Tests\Fixtures;
use Tests\TestCase;

class PasskeyControllerTest extends TestCase implements DatabaseTransaction
{
    use AuthAsserts, PasskeyServiceMocker;

    protected UserFactory $userFactory;

    protected PasskeyFactory $passkeyFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->passkeyFactory = $this->getContainer()->get(PasskeyFactory::class);
    }

    public function testListRequireAuth(): void
    {
        $response = $this->get('/profile/passkey');

        $response->assertUnauthorized();
    }

    public function testListPasskey(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $passkeys = $this->passkeyFactory->forUser($user)->createMany(2);
        $foreign = $this->passkeyFactory->forUser($this->userFactory->create())->createMany(2);

        $response = $this->withAuth($auth)->get('/profile/passkey');

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);

        foreach ($passkeys as $passkey) {
            $this->assertArrayContains($passkey->id, $body, 'data.*.id');
            $this->assertArrayContains($passkey->name, $body, 'data.*.name');
        }

        foreach ($foreign as $passkey) {
            $this->assertArrayNotContains($passkey->id, $body, 'data.*.id');
            $this->assertArrayNotContains($passkey->name, $body, 'data.*.name');
        }
    }

    public function testInitRequireAuth(): void
    {
        $response = $this->post('/profile/passkey/init');

        $response->assertUnauthorized();
    }

    public function initValidationFailsDataProvider(): array
    {
        return [
            [[], ['name']],
            [['name' => ''], ['name']],
            [['name' => 'as'], ['name']],
        ];
    }

    /**
     * @dataProvider initValidationFailsDataProvider
     * @param array $request
     * @param array $expectedErrors
     * @return void
     */
    public function testInitValidationFails(array $request, array $expectedErrors): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/passkey/init', $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testInit(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $savedPasskey = $this->passkeyFactory->forUser($user)->create();

        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();

        /** @var PasskeyConfig $config */
        $config = $this->getContainer()->get(PasskeyConfig::class);
        $passkeyAuthService = $this->makePasskeyAuthMock(
            ['generateChallenge'],
            function (\Redis|MockObject $redis) use ($passkey, $challenge) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())
                      ->method('hMSet')
                      ->with(
                          $key,
                          $this->callback(function(array $data) use ($challenge, $passkey) {
                              $this->assertIsArray($data);
                              $this->assertArrayHasKey('name', $data);
                              $this->assertArrayHasKey('challenge', $data);
                              $this->assertArrayHasKey('options', $data);
                              $this->assertEquals($passkey->name, $data['name']);
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

        $response = $this->withAuth($auth)->post('/profile/passkey/init', [
            'name' => $passkey->name,
        ]);

        $response->assertOk();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('challenge', $body);
        $this->assertEquals($challenge, $body['challenge']);
        $this->assertArrayHasKey('data', $body);
        $this->assertNotEmpty($body['data']);

        $decoded = json_decode(base64_decode($body['data']), true);

        $this->assertArrayHasKey('rp', $decoded);
        $this->assertArrayHasKey('id', $decoded['rp']);
        $this->assertArrayHasKey('name', $decoded['rp']);
        $this->assertEquals($config->getServiceId(), $decoded['rp']['id']);
        $this->assertEquals($config->getServiceName(), $decoded['rp']['name']);

        $this->assertArrayHasKey('user', $decoded);
        $this->assertArrayHasKey('name', $decoded['user']);
        $this->assertArrayHasKey('id', $decoded['user']);
        $this->assertArrayHasKey('displayName', $decoded['user']);

        $this->assertEquals($user->email, $decoded['user']['name']);
        $this->assertEquals(Base64UrlSafe::encodeUnpadded((string) $user->id), $decoded['user']['id']);
        $this->assertEquals($user->fullName(), $decoded['user']['displayName']);

        $this->assertArrayHasKey('challenge', $decoded);
        $this->assertEquals(Base64UrlSafe::encodeUnpadded($challenge), $decoded['challenge']);

        $this->assertArrayHasKey('excludeCredentials', $decoded);
        $this->assertArrayHasKey(0, $decoded['excludeCredentials']);
        $this->assertArrayHasKey('id', $decoded['excludeCredentials'][0]);
        $this->assertEquals(Base64UrlSafe::encodeUnpadded($savedPasskey->keyId), $decoded['excludeCredentials'][0]['id']);
    }

    public function testInitException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $serviceMock = $this->getMockBuilder(PasskeyService::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['init'])
                            ->getMock();

        $serviceMock->expects($this->once())
                    ->method('init')
                    ->with($user)
                    ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(PasskeyService::class, fn () => $serviceMock);

        $passkey = PasskeyFactory::make();

        $response = $this->withAuth($auth)->post('/profile/passkey/init', [
            'name' => $passkey->name,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testStoreRequireAuth(): void
    {
        $response = $this->post('/profile/passkey');

        $response->assertUnauthorized();
    }

    public function storeValidationFailsDataProvider(): array
    {
        return [
            [[], ['challenge', 'data']],
            [['challenge' => '', 'data' => ''], ['challenge', 'data']],
            [['challenge' => 'asd123', 'data' => ''], ['data']],
            [['challenge' => '', 'data' => 'asd123'], ['challenge']],
        ];
    }

    /**
     * @dataProvider storeValidationFailsDataProvider
     * @param array $request
     * @param array $expectedErrors
     * @return void
     */
    public function testStoreValidationFails(array $request, array $expectedErrors): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/profile/passkey', $request);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);

        foreach ($expectedErrors as $expectedError) {
            $this->assertArrayHasKey($expectedError, $body['errors']);
        }
    }

    public function testStore(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();

        $options = $this->makeCreationChallengeOptions($challenge, $user);

        $data = $this->makeCreateData($options, $passkey);

        $this->makePasskeyAuthMock(
            ['generateChallenge'],
            function (\Redis|MockObject $redis) use ($passkey, $challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())->method('hGetAll')->with($key)->willReturn([
                    'name' => $passkey->name,
                    'challenge' => $challenge,
                    'options' => json_encode($options),
                ]);
                $redis->expects($this->once())->method('del')->with($key)->willReturn(0);
                return true;
            },
        );

        $response = $this->withAuth($auth)->post('/profile/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(200);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('name', $body['data']);
        $this->assertEquals($passkey->name, $body['data']['name']);

        $this->assertDatabaseHas('passkeys', [
            'id' => $body['data']['id'],
            'user_id' => $user->id,
        ], [
            'name' => $passkey->name,
        ]);
    }

    public function testStoreInvalidChallenge(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();

        $options = $this->makeCreationChallengeOptions($challenge, $user);

        $data = $this->makeCreateData($options, $passkey);

        $this->makePasskeyAuthMock(
            ['generateChallenge'],
            function (\Redis|MockObject $redis) use ($passkey, $challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->expects($this->once())->method('hGetAll')->with($key)->willReturn(false);
                return true;
            },
        );

        $response = $this->withAuth($auth)->post('/profile/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testStoreException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());

        $challenge = Fixtures::string(32);
        $data = Fixtures::string(64);

        $serviceMock = $this->getMockBuilder(PasskeyService::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['store'])
                            ->getMock();

        $serviceMock->expects($this->once())
                    ->method('store')
                    ->with($user, $challenge, $data)
                    ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(PasskeyService::class, fn () => $serviceMock);

        $response = $this->withAuth($auth)->post('/profile/passkey', [
            'challenge' => $challenge,
            'data' => $data,
        ]);

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);
    }

    public function testDeleteRequireAuth(): void
    {
        $passkey = $this->passkeyFactory->forUser($this->userFactory->create())->create();

        $response = $this->delete("/profile/passkey/{$passkey->id}");

        $response->assertUnauthorized();
    }

    public function testDeleteNotFound(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());
        $id = Fixtures::integer();

        $response = $this->withAuth($auth)->delete("/profile/passkey/{$id}");

        $response->assertNotFound();
    }

    public function testDeleteForeignNotFound(): void
    {
        $passkey = $this->passkeyFactory->forUser($this->userFactory->create())->create();
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->delete("/profile/passkey/{$passkey->id}");

        $response->assertNotFound();
    }

    public function testDelete(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $passkey = $this->passkeyFactory->forUser($user)->create();

        $response = $this->withAuth($auth)->delete("/profile/passkey/{$passkey->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('passkeys', [
            'id' => $passkey->id,
        ]);
    }

    public function testException(): void
    {
        $auth = $this->makeAuth($user = $this->userFactory->create());
        $passkey = $this->passkeyFactory->forUser($user)->create();

        $storeMock = $this->getMockBuilder(PasskeyService::class)
                          ->disableOriginalConstructor()
                          ->onlyMethods(['delete'])
                          ->getMock();
        $storeMock->expects($this->once())
                  ->method('delete')
                  ->with($passkey)
                  ->willThrowException(new \RuntimeException('broken pipe'));

        $this->getContainer()->bind(PasskeyService::class, fn () => $storeMock);

        $response = $this->withAuth($auth)->delete("/profile/passkey/{$passkey->id}");

        $response->assertStatus(500);

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('error', $body);

        $this->assertDatabaseHas('passkeys', [
            'id' => $passkey->id,
        ]);
    }
}
