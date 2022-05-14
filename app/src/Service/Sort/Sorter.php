<?php

namespace App\Service\Sort;

use App\Service\Sort\Sortable;

trait Sorter
{
    /**
     * @param array<array-key, Sortable> $list
     * @param array<array-key, int|string>|null $sort
     * @return array<array-key, Sortable>
     */
    public function applySort(array $list, array $sort = null): array
    {
        if ($sort === null || count($sort) === 0) {
            return $list;
        }

        $newList = [];

        // prepend items which is out of the order
        foreach ($list as $entity) {
            if (in_array($entity->getSortIndex(), $sort)) {
                continue;
            }

            $newList[] = $entity;
        }

        // place ordered items according indexes positions
        foreach ($sort as $index) {
            foreach ($list as $entity) {
                if ($entity->getSortIndex() !== $index) {
                    continue;
                }

                $newList[] = $entity;
            }
        }

        return $newList;
    }
}
