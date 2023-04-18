<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Encrypter;

use App\Config\AppConfig;
use App\Database\Encrypter\Encrypter;
use Tests\Fixtures;
use Tests\TestCase;

class EncrypterTest extends TestCase
{
    public function testDisabled(): void
    {
        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn('');

        $encrypter = new Encrypter($config);

        $string = Fixtures::string();

        $this->assertEquals($string, $encrypter->encrypt($string));
        $this->assertEquals($string, $encrypter->decrypt($string));
    }
}
