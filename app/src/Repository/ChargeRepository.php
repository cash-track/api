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
                      ->where('wallet_id', $walletId)
                      ->orderBy('created_at', 'DESC');

        $query = $this->injectPaginator($query);

        return $query->fetchAll();
    }
}
