<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Database\LimitTagGroup;
use App\Database\Tag;
use App\Security\LimitTagGroupsChecker;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\RepositoryInterface;
use Tests\TestCase;

class LimitTagGroupsCheckerTest extends TestCase
{
    public function testValidNonArrayValue(): void
    {
        $checker = new LimitTagGroupsChecker($this->getContainer()->get(ORMInterface::class));

        $this->assertFalse($checker->valid('not-an-array', Tag::class));
    }

    public function testValidEmptyRole(): void
    {
        $checker = new LimitTagGroupsChecker($this->getContainer()->get(ORMInterface::class));

        $this->assertFalse($checker->valid([
            ['operation' => LimitTagGroup::CONNECTION_AND, 'tags' => [1]],
        ], ''));
    }

    public function testValidRepositoryNotSelectRepository(): void
    {
        $orm = $this->getMockBuilder(ORMInterface::class)->getMock();
        $orm->method('getRepository')->willReturn(
            $this->getMockBuilder(RepositoryInterface::class)->getMock()
        );

        $checker = new LimitTagGroupsChecker($orm);

        $this->assertFalse($checker->valid([
            ['operation' => LimitTagGroup::CONNECTION_AND, 'tags' => [1]],
        ], Tag::class));
    }

    public function testValidGroupNotArray(): void
    {
        $checker = new LimitTagGroupsChecker($this->getContainer()->get(ORMInterface::class));

        $this->assertFalse($checker->valid(['not-an-array-group'], Tag::class));
    }

    public function testValidGroupEmptyTags(): void
    {
        $checker = new LimitTagGroupsChecker($this->getContainer()->get(ORMInterface::class));

        $this->assertFalse($checker->valid([
            ['operation' => LimitTagGroup::CONNECTION_AND, 'tags' => []],
        ], Tag::class));
    }

    public function testValidGroupInvalidTagIdType(): void
    {
        $checker = new LimitTagGroupsChecker($this->getContainer()->get(ORMInterface::class));

        $this->assertFalse($checker->valid([
            ['operation' => LimitTagGroup::CONNECTION_AND, 'tags' => ['abc']],
        ], Tag::class));
    }
}
