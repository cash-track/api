<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\User;
use Cycle\ORM\Select\Repository;

/**
 * @extends Repository<\App\Database\GoogleAccount>
 */
final class GoogleAccountRepository extends Repository
{
    /**
     * @param \App\Database\User $user
     * @return object|null
     */
    public function findByUser(User $user): object|null
    {
        return $this->findOne([
            'user_id' => $user->id,
        ]);
    }
}
