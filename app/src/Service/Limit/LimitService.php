<?php

declare(strict_types=1);

namespace App\Service\Limit;

use App\Database\Limit;
use App\Database\Tag;
use App\Database\Wallet;
use App\Repository\ChargeRepository;
use App\Repository\LimitRepository;
use Cycle\ORM\EntityManagerInterface;

class LimitService
{
    public function __construct(
        private readonly EntityManagerInterface $tr,
        private readonly ChargeRepository $chargeRepository,
        private readonly LimitRepository $limitRepository,
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
                array_map(fn (Tag $tag) => (int) $tag->id, $limit->getTags()),
                $limit->type
            );

            $list[] = new WalletLimit($limit, $amount);
        }

        return $list;
    }

    /**
     * @param \App\Database\Wallet $target
     * @param \App\Database\Wallet $source
     * @return \App\Service\Limit\WalletLimit[]
     */
    public function copy(Wallet $target, Wallet $source): array
    {
        $sourceLimits = $this->limitRepository->findAllByWalletPK((int) $source->id);

        $list = [];

        foreach ($sourceLimits as $sourceLimit) {
            /** @var Limit $sourceLimit */

            $limit = new Limit();
            $limit->type = $sourceLimit->type;
            $limit->amount = $sourceLimit->amount;
            $limit->setWallet($target);

            foreach ($sourceLimit->tags as $tag) {
                $limit->tags->add($tag);
            }

            $this->tr->persist($limit);

            $list[] = new WalletLimit($limit, 0);
        }

        $this->tr->run();

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
