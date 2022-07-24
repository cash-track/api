<?php

declare(strict_types=1);

namespace App\Service\Filter;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select;

// FIXME. PHPCS PSR12 Does not support PHP 8.1 new feature syntax
// @codingStandardsIgnoreStart
enum FilterType: string {
    case ByDateFrom = 'date-from';
    case ByDateTo = 'date-to';

    public function inject(Select|SelectQuery $query, string $value, array $mapping): void
    {
        switch ($this) {
            case self::ByDateFrom:
                $query->where($mapping[$this->value] ?? 'created_at', '>=', new \DateTimeImmutable($value));
                break;
            case self::ByDateTo:
                $query->where($mapping[$this->value] ?? 'created_at', '<=', new \DateTimeImmutable($value));
                break;
        }
    }

    public function validate(string $value): bool
    {
        switch ($this) {
            case self::ByDateFrom:
            case self::ByDateTo:
                try {
                    new \DateTimeImmutable($value);
                } catch (\Throwable) {
                    return false;
                }
                break;
        }

        return true;
    }
}
// @codingStandardsIgnoreEnd
