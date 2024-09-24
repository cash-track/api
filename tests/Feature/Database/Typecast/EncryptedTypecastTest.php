<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Typecast;

use App\Config\AppConfig;
use App\Service\Encrypter\EncrypterInterface;
use App\Database\Typecast\EncryptedTypecast;
use Psr\Log\LoggerInterface;
use Spiral\Encrypter\Exception\EncrypterException;
use Tests\Fixtures;
use Tests\TestCase;

class EncryptedTypecastTest extends TestCase
{
    public function testCastException(): void
    {
        $encrypter = $this->getMockBuilder(EncrypterInterface::class)->getMock();
        $encrypter->method('decrypt')->willThrowException(new EncrypterException());

        $typecast = new EncryptedTypecast($this->getContainer()->get(LoggerInterface::class), $encrypter);
        $typecast->setRules([
            'column' => EncryptedTypecast::QUERY,
            'other' => 'string',
        ]);

        $data = $typecast->cast([
            'column' => 1,
        ]);

        $this->assertArrayHasKey('column', $data);
        $this->assertEquals(1, $data['column']);

        $data = $typecast->cast([
            'column' => 'value',
        ]);

        $this->assertArrayHasKey('column', $data);
        $this->assertEmpty($data['column']);

        $data = $typecast->cast([
            'column' => '',
        ]);

        $this->assertArrayHasKey('column', $data);
        $this->assertEmpty($data['column']);
    }

    public function testUncastException(): void
    {
        $encrypter = $this->getMockBuilder(EncrypterInterface::class)->getMock();
        $encrypter->method('encrypt')->willThrowException(new EncrypterException());

        $typecast = new EncryptedTypecast($this->getContainer()->get(LoggerInterface::class), $encrypter);
        $typecast->setRules([
            'column' => EncryptedTypecast::QUERY,
        ]);

        $data = $typecast->uncast([
            'column' => 'value',
        ]);

        $this->assertArrayHasKey('column', $data);
        $this->assertEquals('value', $data['column']);

        $data = $typecast->cast([
            'column' => '',
        ]);

        $this->assertArrayHasKey('column', $data);
        $this->assertEmpty($data['column']);
    }

    public function testUncastQueryEqual(): void
    {
        $key = Fixtures::string();

        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn($key);
        $this->getContainer()->bind(AppConfig::class, $config);

        /** @var \App\Database\Typecast\EncryptedTypecast $typecast */
        $typecast = $this->getContainer()->get(EncryptedTypecast::class);
        $typecast->setRules([
            'column' => EncryptedTypecast::QUERY,
            'other' => 'string',
        ]);

        $data = $typecast->uncast(['column' => 'data']);

        $this->assertArrayHasKey('column', $data);
        $this->assertNotEmpty($data['column']);

        $dataNew = $typecast->uncast(['column' => 'data']);

        $this->assertArrayHasKey('column', $dataNew);
        $this->assertNotEmpty($dataNew['column']);

        $this->assertEquals($dataNew['column'], $data['column']);
    }

    public function testUncastStoreDifferent(): void
    {
        $key = Fixtures::string();

        $config = $this->getMockBuilder(AppConfig::class)->onlyMethods(['getDbEncrypterKey'])->getMock();
        $config->method('getDbEncrypterKey')->willReturn($key);
        $this->getContainer()->bind(AppConfig::class, $config);

        /** @var \App\Database\Typecast\EncryptedTypecast $typecast */
        $typecast = $this->getContainer()->get(EncryptedTypecast::class);
        $typecast->setRules([
            'column' => EncryptedTypecast::STORE,
            'other' => 'string',
        ]);

        $data = $typecast->uncast(['column' => 'data']);

        $this->assertArrayHasKey('column', $data);
        $this->assertNotEmpty($data['column']);

        $dataNew = $typecast->uncast(['column' => 'data']);

        $this->assertArrayHasKey('column', $dataNew);
        $this->assertNotEmpty($dataNew['column']);

        $this->assertNotEquals($dataNew['column'], $data['column']);
    }
}
