<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

/**
 * @template-extends Repository<\App\Database\Limit>
 */
final class LimitRepository extends Repository
{
    /**
     * @param int $limitId
     * @param int $walletId
     * @return object|null
     */
    public function findByPKByWalletPK(int $limitId, int $walletId)
    {
        return $this->select()
                    ->load('tags')
                    ->wherePK($limitId)
                    ->where('wallet_id', $walletId)
                    ->fetchOne();
    }

    /**
     * @param int $walletId
     * @return array<array-key, \App\Database\Limit>
     */
    public function findAllByWalletPK(int $walletId): array
    {
        return $this->select()
                    ->load('tags')
                    ->where('wallet_id', $walletId)
                    ->fetchAll();
    }
}
