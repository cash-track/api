<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class ChargeRepository extends Repository
{
    use Paginator;

    /**
     * @param int $walletId
     * @return array
     */
    public function findByWalletId(int $walletId)
    {
        $query = $this->select()
                      ->load('user')
                      ->where('wallet_id', $walletId)
                      ->orderBy('created_at', 'DESC');

        $query = $this->injectPaginator($query);

        return $query->fetchAll();
    }

    /**
     * @param string $chargeId
     * @param int $walletId
     * @return object|null
     */
    public function findByPKByWalletPK(string $chargeId, int $walletId)
    {
        return $this->select()->load('user')->wherePK($chargeId)->where('wallet_id', $walletId)->fetchOne();
    }

    /**
     * @param int $walletId
     * @param string|null $type
     * @return float
     */
    public function totalByWalletPK(int $walletId, string $type = null): float
    {
        $query = $this->select()->where('wallet_id', $walletId);

        if (! empty($type)) {
            $query = $query->where('type', $type);
        }

        return (float) $query->sum('amount');
    }
}
