<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Passkey;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class PasskeyTest extends TestCase
{
    public function testGetUser(): void
    {
        $user = UserFactory::make();
        $user->id = Fixtures::integer();

        $passkey = new Passkey();
        $passkey->setUser($user);

        $this->assertEquals($user, $passkey->getUser());
    }

    public function testGetDataFallback(): void
    {
        $passkey = new Passkey();

        $this->assertEquals([], $passkey->getData());

        $passkey->data = '{"0":}';

        $this->assertEquals([], $passkey->getData());
    }

    public function testSetDataMissingKeyId(): void
    {
        $passkey = new Passkey();

        $this->expectException(\RuntimeException::class);

        $passkey->setData([]);
    }

    public function testSetDataCorrupted(): void
    {
        $passkey = new Passkey();

        $passkey->setData([
            'publicKeyCredentialId' => Fixtures::string(),
            'one' => STDOUT,
        ]);

        $this->assertEquals('', $passkey->data);
    }
}
