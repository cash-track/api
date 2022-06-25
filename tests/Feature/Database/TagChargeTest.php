<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\TagCharge;
use Tests\TestCase;

class TagChargeTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new TagCharge();

        $this->assertNotNull($entity);
    }
}
