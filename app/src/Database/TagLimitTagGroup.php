<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity]
class TagLimitTagGroup
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Relation\BelongsTo(target: Tag::class, innerKey: 'tag_id', load: 'lazy')]
    private Tag $tag;

    #[ORM\Relation\BelongsTo(target: LimitTagGroup::class, innerKey: 'limit_tag_group_id', load: 'lazy')]
    private LimitTagGroup $limitTagGroup;

    public function __construct()
    {
        $this->tag = new Tag();
        $this->limitTagGroup = new LimitTagGroup();
    }
}
