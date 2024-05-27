<?php

declare(strict_types=1);

namespace App\Service\Limit;

use App\Database\Limit;
use App\Database\Tag;
use App\Repository\ChargeRepository;
use Cycle\ORM\EntityManagerInterface;

class LimitService
{
    public function __construct(
        private readonly EntityManagerInterface $tr,
        private readonly ChargeRepository $chargeRepository
    ) {
    }

    /**
     * @param array<\App\Database\Limit> $limits
     * @return array<\App\Service\Limit\WalletLimit>
     */
    public function calculate(array $limits): array
    {
        $list = [];

        // TODO. Aggregate all limits using 1 query

        foreach ($limits as $limit) {
            $amount = $this->chargeRepository->totalByWalletPKAndTagPKs(
                $limit->walletId,
                array_map(fn (Tag $tag) => $tag->id, $limit->getTags()),
                $limit->type
            );

            $list[] = new WalletLimit($limit, $amount);
        }

        return $list;
    }

    public function store(Limit $limit): Limit
    {
        $this->tr->persist($limit);
        $this->tr->run();

        return $limit;
    }

    public function delete(Limit $limit): void
    {
        $this->tr->delete($limit);
        $this->tr->run();
    }
}
