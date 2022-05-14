<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\User;
use App\Service\Sort\SortType;

class UserOptionsService
{
    protected const SORT_ROOT = 'sort';

    /**
     * Fetch the order from the user's options and specific order type
     *
     * @param \App\Database\User $user
     * @param \App\Service\Sort\SortType $type
     * @return array
     */
    public function getSort(User $user, SortType $type): array
    {
        if (! array_key_exists(self::SORT_ROOT, $user->options)) {
            return [];
        }

        return $user->options[self::SORT_ROOT][$type->value] ?? [];
    }

    /**
     * Update order of the user's options and given order type
     *
     * @param \App\Database\User $user
     * @param \App\Service\Sort\SortType $type
     * @param array $order
     * @return void
     */
    public function setSort(User $user, SortType $type, array $order): void
    {
        if (! array_key_exists(self::SORT_ROOT, $user->options)) {
            $user->options[self::SORT_ROOT] = [];
        }

        $user->options[self::SORT_ROOT][$type->value] = $order;
    }
}
