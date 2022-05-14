<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Typecast\JsonTypecast;
use Tests\TestCase;

class JsonTypecastTest extends TestCase
{
    public function testSetRules(): void
    {
        $typecast = $this->getContainer()->get(JsonTypecast::class);

        $rules = [
            'options' => JsonTypecast::RULE,
            'key' => 'uuid',
        ];

        $rules = $typecast->setRules($rules);

        $this->assertEquals(['key' => 'uuid'], $rules);
    }

    public function testCast(): void
    {
        $typecast = $this->getContainer()->get(JsonTypecast::class);

        $typecast->setRules([
            'options' => JsonTypecast::RULE,
        ]);

        $values = $typecast->cast(['options' => '{"",}']);
        $this->assertEquals([], $values['options']);

        $values = $typecast->cast(['options' => '']);
        $this->assertEquals([], $values['options']);

        $values = $typecast->cast(['options' => '{"one":1}']);
        $this->assertEquals(['one' => 1], $values['options']);
    }

    public function testUncast(): void
    {
        $typecast = $this->getContainer()->get(JsonTypecast::class);

        $typecast->setRules([
            'options' => JsonTypecast::RULE,
        ]);

        $values = $typecast->uncast(['options' => ['one' => 1]]);
        $this->assertEquals('{"one":1}', $values['options']);

        $values = $typecast->uncast(['options' => []]);
        $this->assertEquals('[]', $values['options']);

        $values = $typecast->uncast(['options' => ['one' => STDOUT]]);
        $this->assertEquals('[]', $values['options']);
    }
}
