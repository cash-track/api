<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class UserRepository extends Repository
{
    /**
     * @param string $email
     * @return object|null
     */
    public function findByEmail($email) {
        return $this->findOne(['email' => $email]);
    }
}
