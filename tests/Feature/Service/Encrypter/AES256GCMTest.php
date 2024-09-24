<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Encrypter;

use App\Service\Encrypter\AES256GCM;
use Spiral\Encrypter\Exception\EncrypterException;
use Tests\Fixtures;
use Tests\TestCase;

class AES256GCMTest extends TestCase
{
    public function testEncryptDecrypt(): void
    {
        $key = Fixtures::string();
        $message = Fixtures::string(256);

        $encrypter = new AES256GCM();
        $encrypted = $encrypter->encrypt($message, $key);

        $this->assertNotEmpty($encrypted);
        $this->assertNotEquals($message, $encrypted);
        $this->assertEquals($message, $encrypter->decrypt($encrypted, $key));
    }

    public function testDecryptError(): void
    {
        $key = Fixtures::string();
        $message = Fixtures::string(256);

        $encrypter = new AES256GCM();
        $encrypted = $encrypter->encrypt($message, $key);

        $this->expectException(EncrypterException::class);

        $this->assertEquals($message, $encrypter->decrypt($encrypted, $key . Fixtures::string(1)));
    }
}
