<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace App\Service\Pagination;

use Spiral\Pagination\PaginableInterface;

/**
 * Generic paginator interface with ability to set/get page and limit values.
 */
interface PaginatorInterface
{
    /**
     * Paginate the target selection and return new paginator instance.
     *
     * @param \Spiral\Pagination\PaginableInterface $target
     * @return \App\Service\Pagination\PaginatorInterface
     */
    public function paginate(PaginableInterface $target): PaginatorInterface;

    /**
     * Compact current paginator state to flat array.
     *
     * @return array
     */
    public function toArray(): array;
}
