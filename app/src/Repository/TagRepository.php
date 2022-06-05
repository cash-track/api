<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\Database\Injection\Expression;
use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Cycle\Database\Injection\Parameter;

class TagRepository extends Repository
{
    /**
     * @param int $userID
     * @return \App\Database\Tag[]
     */
    public function findAllByUserPK(int $userID): array
    {
        /** @var \App\Database\Tag[] $tags */
        $tags = $this->allByUserPK($userID)->fetchAll();

        return $tags;
    }

    /**
     * @param array<array-key, int> $ids
     * @param array<array-key, int> $userIDs
     * @return \App\Database\Tag[]
     */
    public function findAllByPKsAndUserPKs(array $ids, array $userIDs): array
    {
        if (! count($ids) || ! count($userIDs)) {
            return [];
        }

        /** @var \App\Database\Tag[] $tags */
        $tags = $this->select()->where([
            'id' => ['in' => new Parameter($ids)],
            'user_id' => ['in' => new Parameter($userIDs)],
        ])->fetchAll();

        return $tags;
    }

    /**
     * @param int $walletID
     * @return \App\Database\Tag[]
     */
    public function findAllByWalletPK(int $walletID): array
    {
        /**
         * @var \App\Database\Tag[] $tags
         * @psalm-suppress InternalClass
         * @psalm-suppress UndefinedMagicMethod
         */
        $tags = $this->select()
                     ->with('charges.wallet', [
                         'method' => Select\AbstractLoader::LEFT_JOIN,
                     ])
                     ->where('charges.wallet.id', $walletID)
                     ->groupBy('tag.id')
                     ->orderBy(new Expression('count(tag.id)'), SelectQuery::SORT_DESC)
                     ->fetchAll();

        return $tags;
    }

    /**
     * @param int $id
     * @param int $userID
     * @return \App\Database\Tag|object|null
     */
    public function findByPKByUserPK(int $id, int $userID)
    {
        return $this->select()->wherePK($id)->where('user_id', $userID)->fetchOne();
    }

    /**
     * @param int $userID
     * @return \Cycle\ORM\Select
     */
    protected function allByUserPK(int $userID): Select
    {
        return $this->select()->where('user_id', $userID)->orderBy('updated_at', 'DESC');
    }
}
