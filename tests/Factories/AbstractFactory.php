<?php

declare(strict_types=1);

namespace Tests\Factories;

use App\Repository\CurrencyRepository;
use Cycle\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;

abstract class AbstractFactory
{
    /**
     * @param EntityManagerInterface $transaction
     */
    public function __construct(
        protected EntityManagerInterface $transaction,
        protected CurrencyRepository $currencyRepository,
    ) {
    }

    /**
     * @param mixed $instance
     * @return mixed
     * @throws \Throwable
     */
    public function persist($instance)
    {
        $this->transaction->persist($instance);
        $this->transaction->run();

        return $instance;
    }

    public function createMany(int $amount = 1): ArrayCollection
    {
        $items = new ArrayCollection();

        for ($i = 0; $i < $amount; $i++) {
            $items->add($this->create());
        }

        return $items;
    }
}
