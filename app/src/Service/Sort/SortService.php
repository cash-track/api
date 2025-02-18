<?php

declare(strict_types=1);

namespace App\Service\Sort;

use App\Database\User;
use App\Service\UserOptionsService;

final class SortService
{
    public function __construct(
        protected UserOptionsService $optionsService,
    ) {
    }

    public function set(User $user, SortType $type, array $sort): void
    {
        $this->optionsService->setSort($user, $type, $sort);
    }
}
