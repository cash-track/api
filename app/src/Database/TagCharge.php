<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity]
class TagCharge
{
    #[ORM\Column('primary')]
    public int|null $id = null;

    #[ORM\Relation\BelongsTo(target: Tag::class, innerKey: 'tag_id', load: 'lazy')]
    private Tag $tag;

    #[ORM\Relation\BelongsTo(target: Charge::class, innerKey: 'charge_id', load: 'lazy')]
    private Charge $charge;

    public function __construct()
    {
        $this->tag = new Tag();
        $this->charge = new Charge();
    }
}
