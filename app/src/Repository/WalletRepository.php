<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class WalletRepository extends Repository
{
    /**
     * @param $id
     * @param $userID
     * @return object|null
     */
    public function findByPKByUserPK($id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->fetchOne();
    }
}
