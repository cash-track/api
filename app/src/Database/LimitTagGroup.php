<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;
use Cycle\ORM\Collection\Pivoted\PivotedCollection;

#[ORM\Entity]
class LimitTagGroup
{
    const string CONNECTION_AND = 'and';
    const string CONNECTION_OR  = 'or';

    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Column(type: 'int', name: 'limit_id')]
    public int $limitId = 0;

    #[ORM\Column(type: 'enum(and,or)', default: self::CONNECTION_OR)]
    public string $connection = self::CONNECTION_OR;

    #[ORM\Relation\BelongsTo(target: Limit::class, innerKey: 'limit_id', load: 'lazy')]
    private Limit $limit;

    /**
     * @var \Cycle\ORM\Collection\Pivoted\PivotedCollection<int, \App\Database\Tag, \App\Database\TagLimitTagGroup>
     */
    #[ORM\Relation\ManyToMany(
        target: Tag::class,
        through: TagLimitTagGroup::class,
        throughInnerKey: 'limit_tag_group_id',
        throughOuterKey: 'tag_id',
        collection: 'doctrine',
    )]
    public PivotedCollection $tags;

    public function __construct()
    {
        $this->limit = new Limit();
        $this->tags = new PivotedCollection();
    }

    public function getLimit(): Limit
    {
        return $this->limit;
    }

    public function setLimit(Limit $limit): void
    {
        $this->limit = $limit;
        $this->limitId = (int) $limit->id;
    }

    /**
     * @return array<int, \App\Database\Tag>
     */
    public function getTags(): array
    {
        $tags = [];

        foreach ($this->tags->getValues() as $tag) {
            if (! $tag instanceof Tag) {
                continue;
            }

            $tags[] = $tag;
        }

        return $tags;
    }
}
