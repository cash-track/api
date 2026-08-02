<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Limit;
use App\Database\LimitTagGroup;
use App\Database\Wallet;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\Fixtures;

class LimitFactory extends AbstractFactory
{
    protected ?Wallet $wallet = null;

    /**
     * @var array<array-key, \App\Database\Tag>
     */
    protected array $tags = [];

    /**
     * @var array<array-key, array{connection?: string, tags?: array<array-key, \App\Database\Tag>}>
     */
    protected array $tagGroups = [];

    public function forWallet(?Wallet $wallet = null): LimitFactory
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function withTags(array $tags = []): LimitFactory
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param array<array-key, array{connection?: string, tags?: array<array-key, \App\Database\Tag>}> $tagGroups
     */
    public function withTagGroups(array $tagGroups = []): LimitFactory
    {
        $this->tagGroups = $tagGroups;

        return $this;
    }

    public function createManyPerWallet(ArrayCollection $wallets, int $amount = 1): ArrayCollection
    {
        $items = new ArrayCollection();

        $walletBackup = $this->wallet;

        foreach ($wallets as $wallet) {
            $this->forWallet($wallet);

            $limits = $this->createMany($amount);

            foreach ($limits as $limit) {
                $items->add($limit);
            }
        }

        $this->forWallet($walletBackup);

        return $items;
    }

    public function create(?Limit $limit = null): Limit
    {
        $limit = $limit ?? self::make();

        if ($this->wallet !== null) {
            $limit->setWallet($this->wallet);
        }

        foreach ($this->tags as $tag) {
            $limit->tags->add($tag);
        }

        foreach ($this->tagGroups as $group) {
            $tagGroup = new LimitTagGroup();
            $tagGroup->connection = $group['connection'] ?? LimitTagGroup::CONNECTION_OR;
            $tagGroup->setLimit($limit);

            foreach ($group['tags'] ?? [] as $tag) {
                $tagGroup->tags->add($tag);
            }

            $limit->tagGroups[] = $tagGroup;
        }

        $this->persist($limit);

        return $limit;
    }

    public static function make(): Limit
    {
        $limit = new Limit();

        $limit->type = Fixtures::arrayElement([
            Limit::TYPE_INCOME,
            Limit::TYPE_EXPENSE,
        ]);
        $limit->amount = Fixtures::float();
        $limit->createdAt = Fixtures::dateTime();
        $limit->updatedAt = Fixtures::dateTimeAfter($limit->createdAt);

        return $limit;
    }

    public static function income(?Limit $limit = null): Limit
    {
        return self::type(Limit::TYPE_INCOME, $limit);
    }

    public static function expense(?Limit $limit = null): Limit
    {
        return self::type(Limit::TYPE_EXPENSE, $limit);
    }

    public static function type(string $type, ?Limit $limit = null): Limit
    {
        if ($limit === null) {
            $limit = self::make();
        }

        $limit->type = $type;

        return $limit;
    }
}
