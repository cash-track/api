<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Auth\Passkey;

use App\Service\Auth\Passkey\Exception\PasskeyServiceUnavailableException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\DatabaseTransaction;
use Tests\Factories\PasskeyFactory;
use Tests\Factories\UserFactory;
use Tests\Feature\Controller\PasskeyServiceMocker;
use Tests\Fixtures;
use Tests\TestCase;

class PasskeyServiceTest extends TestCase implements DatabaseTransaction
{
    use PasskeyServiceMocker;

    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function testGenerateChallenge(): void
    {
        $service = $this->makePasskeyAuthMock();

        $data = $service->initAuth();

        $this->assertNotEmpty($data->jsonSerialize());
    }

    public function testInitAuthThrowsWhenRedisFailsToStoreRequestOptions(): void
    {
        $service = $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) {
                $redis->method('hMSet')->willThrowException(new \RuntimeException('connection lost'));
            },
        );

        $this->expectException(PasskeyServiceUnavailableException::class);

        $service->initAuth();
    }

    public function testInitThrowsWhenRedisFailsToStoreCreationOptions(): void
    {
        $user = $this->userFactory->create();

        $service = $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) {
                $redis->method('hMSet')->willThrowException(new \RuntimeException('connection lost'));
            },
        );

        $this->expectException(PasskeyServiceUnavailableException::class);

        $service->init($user, 'My Key');
    }

    public function testAuthenticateThrowsWhenRedisFailsToLoadRequestOptions(): void
    {
        $service = $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) {
                $redis->method('hGetAll')->willThrowException(new \RuntimeException('connection lost'));
            },
        );

        $this->expectException(PasskeyServiceUnavailableException::class);

        $service->authenticate(Fixtures::string(32), Fixtures::string());
    }

    public function testStoreThrowsWhenRedisFailsToLoadCreationOptions(): void
    {
        $user = $this->userFactory->create();
        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();
        $options = $this->makeCreationChallengeOptions($challenge, $user);
        $data = $this->makeCreateData($options, $passkey);

        $service = $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) {
                $redis->method('hGetAll')->willThrowException(new \RuntimeException('connection lost'));
            },
        );

        $this->expectException(PasskeyServiceUnavailableException::class);

        $service->store($user, $challenge, $data);
    }

    public function testStoreThrowsWhenRedisFailsToForgetOptions(): void
    {
        $user = $this->userFactory->create();
        $challenge = Fixtures::string(32);
        $passkey = PasskeyFactory::make();
        $options = $this->makeCreationChallengeOptions($challenge, $user);
        $data = $this->makeCreateData($options, $passkey);

        $service = $this->makePasskeyAuthMock(
            [],
            function (\Redis|MockObject $redis) use ($passkey, $challenge, $options) {
                $key = "passkeys:challenge:{$challenge}";
                $redis->method('hGetAll')->with($key)->willReturn([
                    'name' => $passkey->name,
                    'challenge' => $challenge,
                    'options' => json_encode($options),
                ]);
                $redis->method('del')->willThrowException(new \RuntimeException('connection lost'));
            },
        );

        $this->expectException(PasskeyServiceUnavailableException::class);

        $service->store($user, $challenge, $data);
    }
}
