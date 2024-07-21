<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\TagLimit;
use Tests\TestCase;

class TagLimitTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TagLimit();

        $this->assertNotNull($entity);
    }
}
