<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity]
final class TagLimit
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Relation\BelongsTo(target: Tag::class, innerKey: 'tag_id', load: 'lazy')]
    private Tag $tag;

    #[ORM\Relation\BelongsTo(target: Limit::class, innerKey: 'limit_id', load: 'lazy')]
    private Limit $limit;

    public function __construct()
    {
        $this->tag = new Tag();
        $this->limit = new Limit();
    }
}
