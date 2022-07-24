<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Service\Filter\FilterType;
use Cycle\Database\Injection\Fragment;

// FIXME. PHPCS PSR12 Does not support PHP 8.1 new feature syntax
// @codingStandardsIgnoreStart
enum Group: string {
    case ByDay = 'day';
    case ByMonth = 'month';
    case ByYear = 'year';

    protected function format(): string
    {
        return match ($this) {
            self::ByDay => '%Y-%m-%d',
            self::ByMonth => '%Y-%m',
            self::ByYear => '%Y',
        };
    }

    public function getDateInterval(): \DateInterval
    {
        return match ($this) {
            self::ByDay => new \DateInterval('P1D'),
            self::ByMonth => new \DateInterval('P1M'),
            self::ByYear => new \DateInterval('P1Y'),
        };
    }

    public function getQueryFragment(string $column = 'created_at', string $alias = 'date'): Fragment
    {
        return new Fragment("DATE_FORMAT({$column}, '{$this->format()}') AS {$alias}");
    }

    public function createDateByValue(string $value): \DateTimeImmutable|false
    {
        return match ($this) {
            self::ByDay => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "{$value} 00:00:00"),
            self::ByMonth => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "{$value}-01 00:00:00"),
            self::ByYear => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "{$value}-01-01 00:00:00"),
        };
    }

    public function createRangeDateByDateAndFilterType(\DateTimeImmutable $date, FilterType $filter): \DateTimeImmutable
    {
        if ($this === self::ByDay) {
            return $date->setTime(0, 0);
        }

        if ($this === self::ByMonth) {
            if ($filter === FilterType::ByDateFrom) {
                $dateString = "{$date->format('Y-m')}-01";
            } else {
                $dateString = "{$date->format('Y-m-t')}";
            }
        } else {
            if ($filter === FilterType::ByDateFrom) {
                $dateString = "{$date->format('Y')}-01-01";
            } else {
                $dateString = "{$date->format('Y')}-12-31";
            }
        }

        $result = \DateTimeImmutable::createFromFormat('Y-m-d', $dateString);

        if ($result === false) {
            throw new \RuntimeException("Unable to create date instance from string [{$dateString}]");
        }

        return $result->setTime(0, 0);
    }
}
// @codingStandardsIgnoreEnd
