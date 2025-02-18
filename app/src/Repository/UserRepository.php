<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Encrypter\EncrypterInterface;
use App\Database\User;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Repository;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\TokenInterface;
use Cycle\Database\Query\SelectQuery;

/**
 * @extends Repository<\App\Database\User>
 */
final class UserRepository extends Repository implements ActorProviderInterface
{
    /**
     * @param \Cycle\ORM\Select<User> $select
     * @param \App\Service\Encrypter\EncrypterInterface $encrypter
     */
    public function __construct(
        Select $select,
        private readonly EncrypterInterface $encrypter,
    ) {
        parent::__construct($select);
    }

    /**
     * @param \Spiral\Auth\TokenInterface $token
     * @return object|null
     */
    #[\Override]
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
    public function findByEmail(string $email): object|null
    {
        return $this->findOne([
            'email' => $this->encrypter->encrypt($email),
        ]);
    }

    /**
     * @param string $nickName
     * @return object|null
     */
    public function findByNickName(string $nickName): object|null
    {
        return $this->findOne([
            'nick_name' => $this->encrypter->encrypt($nickName),
        ]);
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
     * @return array<array-key, int>
     */
    public function getCommonUserIDs(User $user): array
    {
        $users = $this->findAllByCommonWallets($user);

        if (count($users) === 0) {
            return [(int) $user->id];
        }

        return array_map(fn (User $user) => (int) $user->id, $users);
    }

    /**
     * @param \App\Database\User $user
     * @return \App\Database\User[]
     */
    public function findAllByCommonWallets(User $user): array
    {
        /** @var \App\Database\User[] $users */
        $users = $this->byCommonWallets($user)->fetchAll();

        return $users;
    }

    public function allForNewsletter(?bool $emailConfirmed = null, int $testId = 0): ?SelectQuery
    {
        $query = $this->select()->getBuilder()->getQuery();

        if ($testId > 0) {
            $query?->where('id', $testId);
            return $query;
        }

        if ($emailConfirmed !== null) {
            $query?->where('is_email_confirmed', $emailConfirmed);
        }

        return $query;
    }

    /**
     * @psalm-suppress UndefinedMagicMethod
     * @param \App\Database\User $user
     * @return \Cycle\ORM\Select
     */
    protected function byCommonWallets(User $user): Select
    {
        $commonWallets = $this->select()->getBuilder()->getQuery();

        if ($commonWallets !== null) {
            $commonWallets->columns(['wallet_id'])
                          ->from('user_wallets')
                          ->where('user_id', '=', $user->id);
        }

        $commonUsers = $this->select()->getBuilder()->getQuery();

        if ($commonUsers !== null) {
            $commonUsers->columns(['user_id'])
                        ->from('user_wallets')
                        ->where('wallet_id', 'in', $commonWallets);
        }

        return $this->select()
            ->where('id', 'in', $commonUsers)
            ->groupBy('id')
            ->orderBy('COUNT(user.id)', SelectQuery::SORT_DESC);
    }
}
