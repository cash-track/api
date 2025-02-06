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
 * Simple predictable paginator.
 */
final class Paginator implements PaginatorInterface, \Countable
{
    private int $pageNumber = 1;

    private int $countPages = 1;

    public function __construct(
        private int $limit = 25,
        private int $count = 0,
        private readonly ?string $parameter = null,
    ) {
    }

    /**
     * Get parameter paginator depends on. Environment specific.
     *
     * @return null|string
     */
    public function getParameter(): ?string
    {
        return $this->parameter;
    }

    public function withLimit(int $limit): self
    {
        $paginator = clone $this;
        $paginator->limit = $limit;

        return $paginator;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function withPage(int $number): self
    {
        $paginator = clone $this;
        $paginator->pageNumber = max($number, 0);

        //Real page number
        return $paginator;
    }

    public function withCount(int $count): self
    {
        $paginator = clone $this;

        return $paginator->setCount($count);
    }

    public function getPage(): int
    {
        if ($this->pageNumber < 1) {
            return 1;
        }

        if ($this->pageNumber > $this->countPages) {
            return $this->countPages;
        }

        return $this->pageNumber;
    }

    public function getOffset(): int
    {
        return ($this->getPage() - 1) * $this->limit;
    }

    public function paginate(PaginableInterface $target): PaginatorInterface
    {
        $paginator = clone $this;
        if ($target instanceof \Countable && $paginator->count === 0) {
            $paginator->setCount($target->count());
        }

        $target->limit($paginator->getLimit());
        $target->offset($paginator->getOffset());

        return $paginator;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function countPages(): int
    {
        return $this->countPages;
    }

    public function countDisplayed(): int
    {
        if ($this->getPage() == $this->countPages) {
            return $this->count - $this->getOffset();
        }

        return $this->limit;
    }

    public function isRequired(): bool
    {
        return ($this->countPages > 1);
    }

    public function nextPage(): ?int
    {
        if ($this->getPage() != $this->countPages) {
            return $this->getPage() + 1;
        }

        return null;
    }

    public function previousPage(): ?int
    {
        if ($this->getPage() > 1) {
            return $this->getPage() - 1;
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'count' => $this->count(),
            'countDisplayed' => $this->countDisplayed(),
            'page' => $this->getPage(),
            'pages' => $this->countPages(),
            'perPage' => $this->getLimit(),
            'nextPage' => $this->nextPage(),
            'previousPage' => $this->previousPage(),
        ];
    }

    /**
     * Non-Immutable version of withCount.
     *
     * @param int $count
     * @return self|$this
     */
    private function setCount(int $count): self
    {
        $this->count = max($count, 0);
        if ($this->count > 0) {
            $this->countPages = (int)ceil($this->count / $this->limit);
        } else {
            $this->countPages = 1;
        }

        return $this;
    }
}
