<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Filter\Filter;
use Cycle\Database\Injection\Expression;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\SelectQuery;
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
     * @param array $tagIds
     * @return array
     */
    public function findByWalletIdAndTagIdsWithPagination(int $walletId, array $tagIds = [])
    {
        $query = $this->select()
                      ->load('user')
                      ->load('tags')
                      ->where('wallet_id', $walletId)
                      ->orderBy('created_at', 'DESC');

        if (count($tagIds) > 0) {
            $query = $query->where('tags.id', 'in', new Parameter($tagIds));
        }

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

        if ($type !== null && $type !== '') {
            $query = $query->where('type', $type);
        }

        $this->injectFilter($query);

        return (float) $query->sum('amount');
    }

    /**
     * @param int $walletId
     * @param array<int, int> $tagIds
     * @param string|null $type
     * @return float
     */
    public function totalByWalletPKAndTagPKs(int $walletId, array $tagIds, string $type = null): float
    {
        /** @psalm-suppress InternalClass */
        $query = $this->select()->where('wallet_id', $walletId)->with('tags', [
            'method' => AbstractLoader::LEFT_JOIN,
        ])->where('tags.id', 'in', new Parameter($tagIds));

        if ($type !== null && $type !== '') {
            $query = $query->where('type', $type);
        }

        $builder = $this->select()->getBuilder();
        $tagsIdCol  = $builder->resolve('tags.id');
        $chargesIdCol  = $builder->resolve('id');

        /** @var non-empty-string $chargesAmountCol */
        $chargesAmountCol  = $builder->resolve('amount');

        $query = $query->buildQuery()
                       ->columns([$chargesIdCol, $chargesAmountCol])
                       ->groupBy($chargesIdCol)
                       ->having(new Fragment("count(distinct {$tagsIdCol}) = ?", count($tagIds)));

        return (float) $this->select()
                            ->buildQuery()
                            ->rightJoin($query, 'filtered')
                            ->on($chargesIdCol, '=', 'filtered.id')
                            ->sum($chargesAmountCol);
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

        if ($type !== null && $type !== '') {
            $query = $query->where('type', $type);
        }

        $this->injectFilter($query);

        return (float) $query->sum('amount');
    }

    /**
     * @param int $walletID
     * @param array $tagIDs
     * @param string|null $type
     * @return array<int, float>
     */
    public function totalByWalletPKGroupByTagPKs(int $walletID, array $tagIDs, string $type = null): array
    {
        /** @psalm-suppress InternalClass */
        $query = $this->select()->where('wallet_id', $walletID)->with('tags', [
            'method' => AbstractLoader::LEFT_JOIN,
        ])->where('tags.id', 'in', new Parameter($tagIDs));

        if ($type !== null && $type !== '') {
            $query = $query->where('type', $type);
        }

        $this->injectFilter($query);

        $builder  = $this->select()->getBuilder();
        $tagIDsCol = $builder->resolve('tags.id');
        $amountCol = $builder->resolve('amount');

        $result = $query->buildQuery()
                        ->columns([$tagIDsCol, new Expression("SUM({$amountCol}) as total")])
                        ->groupBy($tagIDsCol)
                        ->fetchAll();

        $data = [];

        foreach ($result as $row) {
            $id = (int) ($row['id'] ?? 0);

            if ($id > 0) {
                $data[$id] = (float) ($row['total'] ?? 0);
            }
        }

        return $data;
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

    public function searchTitle(int $userID, string $query = '', int $limit = 10): array
    {
        $builder  = $this->select()->getBuilder();
        $titleCol = $builder->resolve('title');
        $q        = $builder->getQuery();

        return $q?->columns([$titleCol, new Expression("count({$titleCol}) as count")])
                 ->from('charges charge')
                 ->rightJoin('user_wallets')
                 ->on($builder->resolve('wallet_id'), '=', 'user_wallets.wallet_id')
                 ->on('user_wallets.user_id', '=', new Parameter($userID))
                 ->where($titleCol, 'like', new Fragment("concat('%', ?, '%')", $query, $query, $query, $query))
                 ->groupBy($titleCol)
                 ->orderBy(new Fragment("{$titleCol} like concat(?, '%')"), SelectQuery::SORT_DESC)
                 ->orderBy(new Expression("count({$titleCol})"), SelectQuery::SORT_DESC)
                 ->orderBy(new Fragment("ifnull(nullif(instr({$titleCol}, concat(' ', ?)), 0), 99999)"))
                 ->orderBy(new Fragment("ifnull(nullif(instr({$titleCol}, ?), 0), 99999)"))
                 ->limit($limit)
                 ->fetchAll();
    }
}
