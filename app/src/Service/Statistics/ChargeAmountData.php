<?php

namespace App\Service\Statistics;

use App\Service\Filter\Filter;
use App\Service\Filter\FilterType;

class ChargeAmountData
{
    use Filter;

    protected array $income = [];

    protected array $expense = [];

    public function __construct(
        protected Group $grouping = Group::ByMonth,
    ) {
    }

    public function setIncome(array $data): static
    {
        $this->income = $this->indexDataByKeys($data, $this->grouping);

        return $this;
    }

    public function setExpense(array $data): static
    {
        $this->expense = $this->indexDataByKeys($data, $this->grouping);

        return $this;
    }

    public function format(): array
    {
        $from = $this->getRangeKeyByFilterType(FilterType::ByDateFrom);
        $to = $this->getRangeKeyByFilterType(FilterType::ByDateTo);

        if ($from === null || $to === null) {
            return [];
        }

        $from = (new \DateTimeImmutable())->setTimestamp($from);
        $to = (new \DateTimeImmutable())->setTimestamp($to);
        $interval = $this->grouping->getDateInterval();

        $result = [];

        while ($from <= $to) {
            $key = $from->format('Y-m-d');
            $index = $from->getTimestamp();

            $result[$index] = [
                'date' => $key,
                'timestamp' => $index,
                'income' => $this->income[$index] ?? 0.0,
                'expense' => $this->expense[$index] ?? 0.0,
            ];

            $from = $from->add($interval);
        }

        return array_values($result);
    }

    protected function indexDataByKeys(array $data, Group $grouping): array
    {
        $result = [];

        foreach ($data as $item) {
            $date = $grouping->createDateByValue($item['date'] ?? '');

            if ($date === false) {
                continue;
            }

            $result[$date->getTimestamp()] = (float) ($item['total'] ?? 0);
        }

        return $result;
    }

    protected function getRangeKeyByFilterType(FilterType $filter): ?int
    {
        if ($this->hasFilter($filter)) {
            $date = new \DateTimeImmutable($this->getFilterValue($filter) ?? '');
            return $this->grouping->createRangeDateByDateAndFilterType($date, $filter)->getTimestamp();
        }

        $keys = array_unique(array_merge(array_keys($this->income), array_keys($this->expense)));
        sort($keys);

        if (($amount = count($keys)) === 0) {
            return null;
        }

        return $filter === FilterType::ByDateFrom ? $keys[0] ?? null : $keys[$amount - 1] ?? null;
    }
}
