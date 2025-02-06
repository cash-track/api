<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\Pagination\PaginatorInterface;
use Cycle\ORM\Select;

/**
 * @template T of object
 */
trait Paginator
{
    /**
     * @var \App\Service\Pagination\PaginatorInterface|null
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

    public function getPaginationState(): array
    {
        if ($this->paginator instanceof PaginatorInterface) {
            return $this->paginator->toArray();
        }

        return [];
    }

    /**
     * @param \Cycle\ORM\Select<T> $query
     * @return void
     */
    private function injectPaginator(Select $query): void
    {
        if ($this->paginator instanceof PaginatorInterface) {
            $this->paginator = $this->paginator->paginate($query);
        }
    }
}
