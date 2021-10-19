<?php

declare(strict_types=1);

namespace App\Database;

use Cycle\Annotated\Annotation as Cycle;

/**
 * @Cycle\Entity
 */
class UserWallet
{
    /**
     * @Cycle\Column(type = "primary")
     * @var int|null
     */
    public $id;
}
