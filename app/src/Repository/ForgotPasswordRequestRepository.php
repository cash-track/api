<?php

declare(strict_types=1);

namespace App\Repository;

use Cycle\ORM\Select\Repository;

class ForgotPasswordRequestRepository extends Repository
{
    /**
     * @param string $code
     * @return object|null
     */
    public function findByCode(string $code)
    {
        return $this->findOne(['code' => $code]);
    }
}
