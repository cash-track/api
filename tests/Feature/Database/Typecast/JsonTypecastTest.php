<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Typecast;

use App\Database\Typecast\JsonTypecast;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class JsonTypecastTest extends TestCase
{
    public function testCastEmpty(): void
    {
        $typecast = new JsonTypecast($this->getContainer()->get(LoggerInterface::class));
        $typecast->setRules(['options' => 'json']);

        $this->assertEquals([], $typecast->cast([]));
        $this->assertEquals(['options' => []], $typecast->cast(['options' => '']));
        $this->assertEquals(['options' => []], $typecast->cast(['options' => '[]']));
        $this->assertEquals(['options' => 1], $typecast->cast(['options' => 1]));
    }

    public function testUncastEmpty(): void
    {
        $typecast = new JsonTypecast($this->getContainer()->get(LoggerInterface::class));
        $typecast->setRules(['options' => 'json']);

        $this->assertEquals([], $typecast->uncast([]));
        $this->assertEquals(['options' => 1], $typecast->uncast(['options' => 1]));
    }
}
