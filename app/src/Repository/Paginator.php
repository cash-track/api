<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Pagination\PaginatorInterface;
use Cycle\ORM\Select;

trait Paginator
{
    /**
     * @var \App\Service\Pagination\PaginatorInterface
     */
    protected $paginator;

    /**
     * @param \App\Service\Pagination\PaginatorInterface $paginator
     * @return $this
     */
    public function paginate(PaginatorInterface $paginator)
    {
        $this->paginator = $paginator;

        return $this;
    }

    /**
     * @return array
     */
    public function getPaginationState(): array
    {
        if ($this->paginator instanceof PaginatorInterface) {
            return $this->paginator->toArray();
        }

        return [];
    }

    /**
     * @param \Cycle\ORM\Select $query
     * @return \Cycle\ORM\Select
     */
    private function injectPaginator(Select $query): Select
    {
        if ($this->paginator instanceof PaginatorInterface) {
            $this->paginator = $this->paginator->paginate($query);
        }

        return $query;
    }
}
