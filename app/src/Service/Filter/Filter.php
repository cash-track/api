<?php

declare(strict_types=1);

namespace App\Service\Filter;

use Cycle\ORM\Select;

trait Filter
{
    /**
     * @var array<string, string>
     */
    protected array $filter = [];

    public function filter(array $query): static
    {
        if (count($query) === 0) {
            return $this;
        }

        $this->filter = [];

        foreach (FilterType::cases() as $type) {
            if (! array_key_exists($type->value, $query)) {
                continue;
            }

            try {
                new \DateTimeImmutable($query[$type->value]);
            } catch (\Throwable) {
                continue;
            }

            $this->filter[$type->value] = $query[$type->value];
        }

        return $this;
    }

    protected function injectFilter(Select $query): Select
    {
        if (! count($this->filter)) {
            return $query;
        }

        foreach ($this->filter as $type => $value) {
            try {
                $query = FilterType::from($type)->inject($query, $value);
            } catch (\ValueError) {
                continue;
            }
        }

        return $query;
    }
}
