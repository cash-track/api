<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\User;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\TokenInterface;
use Spiral\Database\Query\SelectQuery;

class UserRepository extends Repository implements ActorProviderInterface
{
    /**
     * @param \Spiral\Auth\TokenInterface $token
     * @return object|null
     */
    public function getActor(TokenInterface $token): ?object
    {
        if (! isset($token->getPayload()['sub'])) {
            return null;
        }

        return $this->findByPK($token->getPayload()['sub']);
    }

    /**
     * @param string $email
     * @return object|null
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * @param \App\Database\User $user
     * @return object[]
     */
    public function findByCommonWallets(User $user): array
    {
        return $this->byCommonWallets($user)
                    ->where('user.id', '!=', $user->id)
                    ->limit(10)
                    ->fetchAll();
    }

    /**
     * @param \App\Database\User $user
     * @return \Cycle\ORM\Select
     */
    protected function byCommonWallets(User $user): Select
    {
        return $this->select()
                    ->where(
                        'wallets.@.wallet_id',
                        'in',
                        $this->select()
                             ->where('wallets.@.user_id', '=', $user->id)
                             ->buildQuery()
                             ->columns('wallet_id')
                    )
                    ->groupBy('user.id')
                    ->orderBy('COUNT(user.id)', SelectQuery::SORT_DESC);
    }
}
