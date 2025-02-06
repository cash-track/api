<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Limit;
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
        return self::type($limit, Limit::TYPE_INCOME);
    }

    public static function expense(?Limit $limit = null): Limit
    {
        return self::type($limit, Limit::TYPE_EXPENSE);
    }

    public static function type(?Limit $limit = null, string $type): Limit
    {
        if ($limit === null) {
            $limit = self::make();
        }

        $limit->type = $type;

        return $limit;
    }
}
