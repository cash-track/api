<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as ORM;

#[ORM\Entity]
#[ORM\Table\Index(['user_id', 'wallet_id'], true)]
class UserWallet
{
    #[ORM\Column('primary')]
    public int|null $id = null;
}
