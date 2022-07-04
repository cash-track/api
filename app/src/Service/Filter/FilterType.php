<?php

declare(strict_types=1);

namespace App\Service\Filter;

use Cycle\ORM\Select;

// FIXME. PHPCS PSR12 Does not support PHP 8.1 new feature syntax
// @codingStandardsIgnoreStart
enum FilterType: string {
    case ByDateFrom = 'date-from';
    case ByDateTo = 'date-to';

    public function inject(Select $query, string $value): Select
    {
        switch ($this) {
            case self::ByDateFrom:
                $query->where('created_at', '>=', new \DateTimeImmutable($value));
                break;
            case self::ByDateTo:
                $query->where('created_at', '<=', new \DateTimeImmutable($value));
                break;
        }

        return $query;
    }
}
// @codingStandardsIgnoreEnd
