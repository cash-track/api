<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\EntityHeader;
use App\Database\User;
use Tests\TestCase;

class EntityHeaderTest extends TestCase
{
    public function testToPayload(): void
    {
        $header = new EntityHeader(User::class, ['id' => 123]);

        $this->assertEquals([
            'class' => EntityHeader::class,
            'attrs' => [User::class, ['id' => 123]]
        ], $header->toPayload());
    }

    public function testFromPayload(): void
    {
        $header = EntityHeader::fromPayload([
            'class' => EntityHeader::class,
            'attrs' => [User::class, ['id' => 123]]
        ]);

        $this->assertInstanceOf(EntityHeader::class, $header);
        $this->assertEquals(User::class, $header->role);
        $this->assertEquals(['id' => 123], $header->params);
    }
}
