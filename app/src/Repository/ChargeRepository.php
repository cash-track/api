<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;

class ChargeRepository extends Repository
{
    use Paginator;

    /**
     * @param int $walletId
     * @param int|null $tagId
     * @return array
     */
    public function findByWalletId(int $walletId, int $tagId = null)
    {
        $query = $this->select()
                      ->load('user')
                      ->where('wallet_id', $walletId)
                      ->orderBy('created_at', 'DESC');

        if ($tagId !== null) {
            $query = $query->where('tags.id', $tagId);
        }

        $query = $this->injectPaginator($query);

        return $query->fetchAll();
    }

    /**
     * @param int $walletId
     * @param int $limit
     * @return \App\Database\Charge[]
     */
    public function findByWalletIDLatest(int $walletId, int $limit = 4): array
    {
        /** @var \App\Database\Charge[] $charges */
        $charges = $this->select()
                    ->load('user')
                    ->where('wallet_id', $walletId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->fetchAll();

        return $charges;
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
    public function totalByWalletPK(int $walletId, string $type = null, int $tagId = null): float
    {
        $query = $this->select()->where('wallet_id', $walletId);

        if (! empty($type)) {
            $query = $query->where('type', $type);
        }

        if ($tagId !== null) {
            /** @psalm-suppress InternalClass */
            $query = $query->with('tags', [
                'method' => AbstractLoader::LEFT_JOIN,
            ])->where('tags.id', $tagId);
        }

        return (float) $query->sum('amount');
    }

    /**
     * @param string $type
     * @param array $walletIDs
     * @param \DateTimeImmutable|null $dateFrom
     * @return float
     */
    public function sumTotalByTypeByCurrencyFromDate(string $type, array $walletIDs, \DateTimeImmutable $dateFrom = null): float
    {
        if (count($walletIDs) === 0) {
            return 0.0;
        }

        $query = $this->select()
                      ->where('type', $type)
                      ->where('wallet_id', 'in', new Parameter($walletIDs));

        if ($dateFrom !== null) {
            $query = $query->where('created_at', '>=', $dateFrom);
        }

        return (float) $query->sum('amount');
    }

    /**
     * @param int $userID
     * @param string|null $type
     * @return int
     */
    public function countAllByUserPKByType(int $userID, string $type = null): int
    {
        $query = $this->select()->where('user_id', $userID);

        if ($type !== null) {
            $query = $query->where('type', $type);
        }

        return $query->count();
    }
}
