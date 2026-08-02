<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\TagLimitTagGroup;
use Tests\TestCase;

class TagLimitTagGroupTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TagLimitTagGroup();

        $this->assertNotNull($entity);
    }
}
