<?php

namespace App\Service\Sort;

interface Sortable
{
    /**
     * Return a key that will be used in sortable options
     *
     * @return int|string
     */
    public function getSortIndex(): int|string;
}
