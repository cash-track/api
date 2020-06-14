<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class ChargeRepository extends Repository
{
    /**
     * @param int $walletId
     * @return array
     */
    public function findByWalletId(int $walletId)
    {
        // TODO. Implement pagination

        return $this->select()
                    ->where('wallet_id', $walletId)
                    ->orderBy('created_at', 'DESC')
                    ->fetchAll();
    }
}
