<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

/**
 * @extends Repository<\App\Database\EmailConfirmation>
 */
class EmailConfirmationRepository extends Repository
{
    /**
     * @param string $token
     * @return object|null
     */
    public function findByToken(string $token)
    {
        return $this->findOne(['token' => $token]);
    }
}
