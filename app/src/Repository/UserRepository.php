<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;
use Spiral\Auth\ActorProviderInterface;
use Spiral\Auth\TokenInterface;

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
}
