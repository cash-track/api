<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;

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
