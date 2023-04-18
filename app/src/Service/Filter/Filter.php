<?php

declare(strict_types=1);

namespace App\Service\Filter;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select;

trait Filter
{
    /**
     * @var array<string, string>
     */
    protected array $filter = [];

    public function filter(array $query): static
    {
        $this->filter = [];

        if (count($query) === 0) {
            return $this;
        }

        foreach (FilterType::cases() as $type) {
            if (! array_key_exists($type->value, $query)) {
                continue;
            }

            if (! $type->validate($query[$type->value])) {
                continue;
            }

            $this->filter[$type->value] = $query[$type->value];
        }

        return $this;
    }

    public function hasFilter(FilterType $filter): bool
    {
        return array_key_exists($filter->value, $this->filter);
    }

    public function getFilterValue(FilterType $filter): ?string
    {
        return $this->filter[$filter->value] ?? null;
    }

    protected function filterColumnsMapping(): array
    {
        return [
            FilterType::ByDateFrom->value => 'created_at',
            FilterType::ByDateTo->value => 'created_at',
        ];
    }

    protected function injectFilter(Select|SelectQuery $query): void
    {
        if (! count($this->filter)) {
            return;
        }

        foreach ($this->filter as $type => $value) {
            try {
                FilterType::from($type)->inject($query, $value, $this->filterColumnsMapping());
            } catch (\ValueError) {
                continue;
            }
        }
    }
}
