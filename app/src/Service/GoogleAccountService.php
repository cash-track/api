<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\GoogleAccount;
use Cycle\ORM\EntityManagerInterface;

final class GoogleAccountService
{
    public function __construct(
        private readonly EntityManagerInterface $tr,
    ) {
    }

    public function store(GoogleAccount $googleAccount): GoogleAccount
    {
        $this->tr->persist($googleAccount);
        $this->tr->run();

        return $googleAccount;
    }
}
