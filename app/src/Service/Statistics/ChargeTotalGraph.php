<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\TagRepository;
use App\Service\Filter\Filter;
use App\Service\Filter\FilterType;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;

class ChargeTotalGraph
{
    /**
     * @use Filter<\App\Database\Charge>
     */
    use Filter;

    protected array $tagIds = [];

    public function __construct(
        protected readonly ChargeRepository $chargeRepository,
        protected readonly TagRepository $tagRepository,
    ) {
    }

    public function groupByTags(array $ids): static
    {
        $this->tagIds = $ids;

        return $this;
    }

    public function getGraph(Wallet $wallet): array
    {
        $query = $this->buildQueryByWalletAndType($wallet);

        $data = new ChargeTotalData();
        $data->setData($query->fetchAll());

        return $data->format();
    }

    protected function buildQueryByWalletAndType(Wallet $wallet): SelectQuery
    {
        return $this->buildQuery()->where('charges.wallet_id', $wallet->id);
    }

    protected function buildQuery(): SelectQuery
    {
        $query = $this->chargeRepository->select()->buildQuery();

        $columns = [
            new Fragment('SUM(distinct charges.amount) AS total'),
            new Fragment('aggregated.tag_ids AS tag_ids'),
        ];

        $query = $query->from('charges')
                       ->columns($columns)
                       ->leftJoin('tag_charges')
                       ->on('tag_charges.charge_id', 'charges.id')
                       ->leftJoin('charges_tags_aggregated', 'aggregated')
                       ->on('aggregated.id', 'charges.id')
                       ->groupBy('aggregated.tag_ids')
                       ->orderBy('total', SelectQuery::SORT_DESC);

        if (count($this->tagIds) > 0) {
            $query = $query->where('tag_charges.tag_id', 'in', new Parameter($this->tagIds));
        }

        $this->injectFilter($query);

        return $query;
    }

    protected function filterColumnsMapping(): array
    {
        return [
            FilterType::ByDateFrom->value => 'charges.created_at',
            FilterType::ByDateTo->value => 'charges.created_at',
            FilterType::ByChargeType->value => 'charges.type',
        ];
    }
}
