<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class WalletRepository extends Repository
{
    /**
     * @param $userID
     * @return object[]
     */
    public function findAllByUserPK($userID)
    {
        return $this->select()->where('users.id', $userID)->orderBy('created_at', 'DESC')->fetchAll();
    }

    /**
     * @param $id
     * @param $userID
     * @return object|null
     */
    public function findByPKByUserPK($id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->fetchOne();
    }

    /**
     * @param $id
     * @param $userID
     * @return object|null
     */
    public function findByPKByUserPKWithUsers($id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->with('users')->fetchOne();
    }
}
