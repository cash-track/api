<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Currency;
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
     * @param int $limit
     * @return \App\Database\Wallet[]
     */
    public function findAllUnArchivedByUserPKWithLimit(int $userID, int $limit = 4): array
    {
        /** @var \App\Database\Wallet[] $wallets */
        $wallets = $this->allByUserPK($userID)->where('is_archived', false)->limit($limit)->fetchAll();

        return $wallets;
    }

    /**
     * @param int $userID
     * @param string $currencyCode
     * @return \App\Database\Wallet[]
     */
    public function findAllByUserPKByCurrencyCode(int $userID, string $currencyCode = Currency::DEFAULT_CURRENCY_CODE): array
    {
        /** @var \App\Database\Wallet[] $wallets */
        $wallets = $this->select()
                        ->where('users.id', $userID)
                        ->where('default_currency_code', $currencyCode)
                        ->fetchAll();

        return $wallets;
    }

    /**
     * @param int $userID
     * @return int
     */
    public function countAllByUserPK(int $userID): int
    {
        return $this->allByUserPK($userID)->count();
    }

    /**
     * @param int $userID
     * @return int
     */
    public function countArchivedByUserPK(int $userID): int
    {
        return $this->allByUserPK($userID)->where('is_archived', true)->count();
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
