<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;

class WalletRepository extends Repository
{
    /**
     * @param $userID
     * @return \App\Database\Wallet[]|object[]
     */
    public function findAllByUserPK($userID): array
    {
        return $this->allByUserPK($userID)->fetchAll();
    }

    /**
     * @param $userID
     * @param bool $isArchived
     * @return \App\Database\Wallet[]|object[]
     */
    public function findAllByUserPKByArchived($userID, bool $isArchived = false): array
    {
        return $this->allByUserPK($userID)->where('is_archived', $isArchived)->fetchAll();
    }

    /**
     * @param $userID
     * @return \Cycle\ORM\Select
     */
    protected function allByUserPK($userID): Select
    {
        return $this->select()->load('users')->where('users.id', $userID)->orderBy('created_at', 'DESC');
    }

    /**
     * @param $id
     * @param $userID
     * @return \App\Database\Wallet|object|null
     */
    public function findByPKByUserPK($id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->fetchOne();
    }

    /**
     * @param $id
     * @param $userID
     * @return \App\Database\Wallet|object|null
     */
    public function findByPKByUserPKWithUsers($id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->with('users')->fetchOne();
    }
}
