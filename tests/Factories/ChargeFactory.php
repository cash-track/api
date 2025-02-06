<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Database\Charge;
use App\Database\User;
use App\Database\Wallet;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\Fixtures;

class ChargeFactory extends AbstractFactory
{
    protected ?Wallet $wallet = null;

    protected ?User $user = null;

    /**
     * @var array<array-key, \App\Database\Tag>
     */
    protected array $tags = [];

    public function forWallet(?Wallet $wallet = null): ChargeFactory
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function forUser(?User $user = null): ChargeFactory
    {
        $this->user = $user;

        return $this;
    }

    public function withTags(array $tags = []): ChargeFactory
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

            $charges = $this->createMany($amount);

            foreach ($charges as $charge) {
                $items->add($charge);
            }
        }

        $this->forWallet($walletBackup);

        return $items;
    }

    public function create(?Charge $charge = null): Charge
    {
        $charge = $charge ?? self::make();

        if ($this->wallet !== null) {
            $charge->setWallet($this->wallet);
        }

        if ($this->user !== null) {
            $charge->setUser($this->user);
        }

        foreach ($this->tags as $tag) {
            $charge->tags->add($tag);
        }

        $this->persist($charge);

        return $charge;
    }

    public static function make(): Charge
    {
        $charge = new Charge();

        $charge->type = Fixtures::arrayElement([
            Charge::TYPE_INCOME,
            Charge::TYPE_EXPENSE,
        ]);
        $charge->amount = Fixtures::float();
        $charge->title = Fixtures::string();
        $charge->description = Fixtures::boolean() ? Fixtures::string() : '';

        $charge->createdAt = Fixtures::dateTime();
        $charge->updatedAt = Fixtures::dateTimeAfter($charge->createdAt);

        return $charge;
    }

    public static function income(?Charge $charge = null): Charge
    {
        return self::type($charge, Charge::TYPE_INCOME);
    }

    public static function expense(?Charge $charge = null): Charge
    {
        return self::type($charge, Charge::TYPE_EXPENSE);
    }

    public static function type(?Charge $charge = null, string $type = Charge::TYPE_EXPENSE): Charge
    {
        if ($charge === null) {
            $charge = self::make();
        }

        $charge->type = $type;

        return $charge;
    }
}
