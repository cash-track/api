<?php

declare(strict_types=1);

namespace App\Service\Statistics;

use App\Database\Charge;
use App\Database\Tag;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Service\Filter\Filter;
use App\Service\Filter\FilterType;
use Cycle\Database\Injection\Fragment;
use Cycle\Database\Injection\Parameter;
use Cycle\Database\Query\SelectQuery;

class ChargeAmountGraph
{
    /**
     * @use Filter<\App\Database\Charge>
     */
    use Filter;

    protected Group $grouping = Group::ByMonth;

    protected array $tagIds = [];

    public function __construct(
        protected readonly ChargeRepository $chargeRepository
    ) {
    }

    public function groupBy(?string $value = null, Group $default = Group::ByMonth): static
    {
        $this->grouping = Group::tryFrom($value ?? '') ?? $default;

        return $this;
    }

    public function groupByTags(array $ids): static
    {
        $this->tagIds = $ids;

        return $this;
    }

    public function getGraph(?Tag $tag = null, ?Wallet $wallet = null): array
    {
        $query = $this->buildQueryByTagAndWallet($tag, $wallet);

        $data = new ChargeAmountData($this->grouping);
        $data->filter($this->filter);
        $data->setTagIds($this->tagIds);
        $data->setIncome((clone $query)->where('charges.type', Charge::TYPE_INCOME)->fetchAll());
        $data->setExpense((clone $query)->where('charges.type', Charge::TYPE_EXPENSE)->fetchAll());

        return $data->format();
    }

    protected function buildQueryByTagAndWallet(?Tag $tag = null, ?Wallet $wallet = null): SelectQuery
    {
        $query = $this->buildQuery();

        if ($tag !== null) {
            $query = $query->where('tag_charges.tag_id', $tag->id);
        } else if (count($this->tagIds) > 0) {
            $query = $query->where('tag_charges.tag_id', 'in', new Parameter($this->tagIds));
        }

        if ($wallet !== null) {
            $query = $query->where('charges.wallet_id', $wallet->id);
        }

        return $query;
    }

    protected function buildQuery(): SelectQuery
    {
        $query = $this->chargeRepository->select()->buildQuery();

        $columns = [
            $this->grouping->getQueryFragment('charges.created_at'),
            new Fragment('SUM(charges.amount) AS total'),
        ];

        if (count($this->tagIds) > 0) {
            $columns[] = new Fragment('tag_charges.tag_id AS tag_id');
        }

        $query = $query->from('charges')
                       ->columns($columns)
                       ->leftJoin('tag_charges')
                       ->on('tag_charges.charge_id', 'charges.id')
                       ->groupBy('date');

        if (count($this->tagIds) > 0) {
            $query = $query->groupBy('tag_id');
        }

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
