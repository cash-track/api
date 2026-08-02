<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Database\Limit;
use App\Database\LimitTagGroup;
use Tests\TestCase;

class LimitTagGroupTest extends TestCase
{
    public function testCreateEntity(): void
    {
        $entity = new LimitTagGroup();

        $this->assertNotNull($entity);
    }

    public function testGetSetLimit(): void
    {
        $tagGroup = new LimitTagGroup();
        $limit = new Limit();
        $limit->id = 5;

        $tagGroup->setLimit($limit);

        $this->assertSame($limit, $tagGroup->getLimit());
        $this->assertSame(5, $tagGroup->limitId);
    }

    public function testGetTagsVerifyType(): void
    {
        $tagGroup = new LimitTagGroup();
        $tagGroup->tags->add(null);

        $this->assertCount(0, $tagGroup->getTags());
    }
}
