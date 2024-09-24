<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Encrypter;

use App\Config\AppConfig;
use App\Service\Encrypter\Encrypter;
use Spiral\Encrypter\Exception\EncrypterException;
use Tests\Fixtures;
use Tests\TestCase;

class EncrypterTest extends TestCase
{
    public function testEnabled(): void
    {
        $key = Fixtures::string();

        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn($key);

        $encrypter = new Encrypter($config);

        $string = Fixtures::string();
        $encrypted = $encrypter->encrypt($string);

        $this->assertNotEquals($string, $encrypted);
        $this->assertEquals($string, $encrypter->decrypt($encrypted));
    }

    public function testDisabled(): void
    {
        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn('');

        $encrypter = new Encrypter($config);

        $string = Fixtures::string();

        $this->assertEquals($string, $encrypter->encrypt($string));
        $this->assertEquals($string, $encrypter->decrypt($string));
    }

    public function testDecryptThrowException(): void
    {
        $key = Fixtures::string();

        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn($key);

        $encrypter = new Encrypter($config);

        $string = Fixtures::string();
        $encrypted = $encrypter->encrypt($string);

        $this->assertNotEquals($string, $encrypted);

        $this->expectException(EncrypterException::class);

        $encrypted .= Fixtures::string(1);

        $this->assertNotEquals($string, $encrypter->decrypt($encrypted));
    }
}
