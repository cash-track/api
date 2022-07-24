<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Database\Charge;
use App\Database\Tag;
use App\Repository\ChargeRepository;
use App\Service\Filter\Filter;
use App\Service\Filter\FilterType;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Query\SelectQuery;

class ChargeAmountGraph
{
    use Filter;

    protected Group $grouping = Group::ByMonth;

    public function __construct(
        private readonly ChargeRepository $chargeRepository
    ) {
    }

    public function groupBy(string $value = null, Group $default = Group::ByMonth): static
    {
        $this->grouping = Group::tryFrom($value ?? '') ?? $default;

        return $this;
    }

    public function getGraphByTag(Tag $tag): array
    {
        $query = $this->buildQueryByTag($tag);

        $data = new ChargeAmountData($this->grouping);
        $data->filter($this->filter);
        $data->setIncome((clone $query)->where('charges.type', Charge::TYPE_INCOME)->fetchAll());
        $data->setExpense((clone $query)->where('charges.type', Charge::TYPE_EXPENSE)->fetchAll());

        return $data->format();
    }

    protected function buildQueryByTag(Tag $tag): SelectQuery
    {
        return $this->buildQuery()->where('tag_charges.tag_id', $tag->id);
    }

    protected function buildQuery(): SelectQuery
    {
        $query = $this->chargeRepository->select()->buildQuery();

        $query = $query->from('charges')
                       ->columns([
                           $this->grouping->getQueryFragment('charges.created_at'),
                           new Fragment('SUM(charges.amount) AS total')
                       ])
                       ->leftJoin('tag_charges')
                       ->on('tag_charges.charge_id', 'charges.id')
                       ->groupBy('date');

        $this->injectFilter($query);

        return $query;
    }

    protected function filterColumnsMapping(): array
    {
        return [
            FilterType::ByDateFrom->value => 'charges.created_at',
            FilterType::ByDateTo->value => 'charges.created_at',
        ];
    }
}
