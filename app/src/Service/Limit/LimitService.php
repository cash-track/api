<?php

declare(strict_types=1);

namespace App\Service\Limit;

use App\Database\Limit;
use App\Database\LimitTagGroup;
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
            $limitAmount = 0.0;
            $correctionAmount = 0.0;

            // Each group's amount is summed literally: charges matched by more than one
            // group are counted again for every group they match, by design.
            foreach ($limit->getTagGroups() as $tagGroup) {
                $tagIds = array_map(fn (Tag $tag) => (int) $tag->id, $tagGroup->getTags());

                if ($tagIds === []) {
                    continue;
                }

                $limitAmount += $this->chargeRepository->totalByWalletPKAndTagPKs(
                    $limit->walletId,
                    $tagIds,
                    $limit->type,
                    $tagGroup->connection,
                );

                // calculate total for opposite limit type to get the correction amount
                $correctionAmount += $this->chargeRepository->totalByWalletPKAndTagPKs(
                    $limit->walletId,
                    $tagIds,
                    $limit->type == Limit::TYPE_INCOME ? Limit::TYPE_EXPENSE : Limit::TYPE_INCOME,
                    $tagGroup->connection,
                );
            }

            $amount = 0;

            if ($correctionAmount < $limitAmount) {
                $amount = round($limitAmount - $correctionAmount, 2);
            }

            $list[] = new WalletLimit($limit, $amount);
        }

        usort($list, function (WalletLimit $a, WalletLimit $b) {
            return $b->percentage <=> $a->percentage;
        });

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

        $limits = [];
        $list = [];

        foreach ($sourceLimits as $sourceLimit) {
            /** @var Limit $sourceLimit */

            $limit = new Limit();
            $limit->type = $sourceLimit->type;
            $limit->amount = $sourceLimit->amount;
            $limit->setWallet($target);

            foreach ($sourceLimit->getTagGroups() as $sourceTagGroup) {
                $tagGroup = new LimitTagGroup();
                $tagGroup->connection = $sourceTagGroup->connection;
                $tagGroup->setLimit($limit);

                foreach ($sourceTagGroup->getTags() as $tag) {
                    $tagGroup->tags->add($tag);
                }

                $limit->tagGroups[] = $tagGroup;
            }

            $limits[] = $limit;

            $list[] = new WalletLimit($limit, 0);
        }

        foreach ($limits as $limit) {
            $this->tr->persist($limit);
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
