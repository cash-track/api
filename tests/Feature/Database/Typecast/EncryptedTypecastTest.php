<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Typecast;

use App\Database\Encrypter\EncrypterInterface;
use App\Database\Typecast\EncryptedTypecast;
use Psr\Log\LoggerInterface;
use Spiral\Encrypter\Exception\EncrypterException;
use Tests\TestCase;

class EncryptedTypecastTest extends TestCase
{
    public function testCastException(): void
    {
        $encrypter = $this->getMockBuilder(EncrypterInterface::class)->getMock();
        $encrypter->method('decrypt')->willThrowException(new EncrypterException());

        $typecast = new EncryptedTypecast($this->getContainer()->get(LoggerInterface::class), $encrypter);
        $typecast->setRules([
            'column' => EncryptedTypecast::RULE,
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
            'column' => EncryptedTypecast::RULE,
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
}
