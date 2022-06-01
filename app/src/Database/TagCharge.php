<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity]
class TagCharge
{
    #[ORM\Column('primary')]
    public int|null $id = null;
}
