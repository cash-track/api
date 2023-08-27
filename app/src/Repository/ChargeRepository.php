<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Filter\Filter;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;

/**
 * @extends Repository<\App\Database\Charge>
 */
class ChargeRepository extends Repository
{
    /**
     * @use Paginator<\App\Database\Charge>
     */
    use Paginator;

    /**
     * @use Filter<\App\Database\Charge>
     */
    use Filter;

    /**
     * @param string $chargeId
     * @param int $walletId
     * @return object|null
     */
    public function findByPKByWalletPK(string $chargeId, int $walletId)
    {
        return $this->select()
                    ->load('user')
                    ->load('tags')
                    ->wherePK($chargeId)
                    ->where('wallet_id', $walletId)
                    ->fetchOne();
    }

    /**
     * @param array $chargeIds
     * @param int $walletId
     * @return array<array-key, \App\Database\Charge>
     */
    public function findByPKsByWalletPK(array $chargeIds, int $walletId)
    {
        return $this->select()
                    ->wherePK(...$chargeIds)
                    ->where('wallet_id', $walletId)
                    ->fetchAll();
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
                        ->load('tags')
                        ->where('wallet_id', $walletId)
                        ->orderBy('created_at', 'DESC')
                        ->limit($limit)
                        ->fetchAll();

        return $charges;
    }

    /**
     * @param int $walletId
     * @return array
     */
    public function findByWalletIdWithPagination(int $walletId)
    {
        $query = $this->select()
                      ->load('user')
                      ->load('tags')
                      ->where('wallet_id', $walletId)
                      ->orderBy('created_at', 'DESC');

        $this->injectFilter($query);
        $this->injectPaginator($query);

        return $query->fetchAll();
    }

    /**
     * @param int $tagId
     * @return \App\Database\Charge[]
     */
    public function findByTagIdWithPagination(int $tagId): array
    {
        $query = $this->select()
                      ->load('user')
                      ->load('tags')
                      ->load('wallet')
                      ->where('tags.id', $tagId)
                      ->orderBy('created_at', 'DESC');

        $this->injectFilter($query);
        $this->injectPaginator($query);

        /** @var \App\Database\Charge[] $charges */
        $charges = $query->fetchAll();

        return $charges;
    }

    /**
     * @param int $walletId
     * @param int $tagId
     * @return array
     */
    public function findByWalletIdAndTagIdWithPagination(int $walletId, int $tagId = null)
    {
        $query = $this->select()
                      ->load('user')
                      ->load('tags')
                      ->where('wallet_id', $walletId)
                      ->where('tags.id', $tagId)
                      ->orderBy('created_at', 'DESC');

        $this->injectFilter($query);
        $this->injectPaginator($query);

        return $query->fetchAll();
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

        $this->injectFilter($query);

        return (float) $query->sum('amount');
    }

    /**
     * @param int $tagId
     * @param string|null $type
     * @return float
     */
    public function totalByTagPK(int $tagId, string $type = null): float
    {
        /** @psalm-suppress InternalClass */
        $query = $this->select()->with('tags', [
            'method' => AbstractLoader::LEFT_JOIN,
        ])->where('tags.id', $tagId);

        if (! empty($type)) {
            $query = $query->where('type', $type);
        }

        $this->injectFilter($query);

        return (float) $query->sum('amount');
    }

    /**
     * @param int $walletId
     * @param int $tagId
     * @param string|null $type
     * @return float
     */
    public function totalByWalletPKAndTagId(int $walletId, int $tagId, string $type = null): float
    {
        /** @psalm-suppress InternalClass */
        $query = $this->select()->where('wallet_id', $walletId)->with('tags', [
            'method' => AbstractLoader::LEFT_JOIN,
        ])->where('tags.id', $tagId);

        if (! empty($type)) {
            $query = $query->where('type', $type);
        }

        $this->injectFilter($query);

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
