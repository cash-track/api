<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;

class WalletRepository extends Repository
{
    /**
     * @param int $userID
     * @return \App\Database\Wallet[]
     */
    public function findAllByUserPK(int $userID): array
    {
        /** @var \App\Database\Wallet[] $wallets */
        $wallets = $this->allByUserPK($userID)->fetchAll();

        return $wallets;
    }

    /**
     * @param int $userID
     * @param bool $isArchived
     * @return \App\Database\Wallet[]
     */
    public function findAllByUserPKByArchived(int $userID, bool $isArchived = false): array
    {
        /** @var \App\Database\Wallet[] $wallets */
        $wallets = $this->allByUserPK($userID)->where('is_archived', $isArchived)->fetchAll();

        return $wallets;
    }

    /**
     * @param int $userID
     * @return \Cycle\ORM\Select
     */
    protected function allByUserPK(int $userID): Select
    {
        return $this->select()->load('users')->where('users.id', $userID)->orderBy('created_at', 'DESC');
    }

    /**
     * @param int $id
     * @param int $userID
     * @return \App\Database\Wallet|object|null
     */
    public function findByPKByUserPK(int $id, $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->fetchOne();
    }

    /**
     * @param int $id
     * @param int $userID
     * @return \App\Database\Wallet|object|null
     */
    public function findByPKByUserPKWithUsers(int $id, int $userID)
    {
        return $this->select()->wherePK($id)->where('users.id', $userID)->with('users')->fetchOne();
    }
}
