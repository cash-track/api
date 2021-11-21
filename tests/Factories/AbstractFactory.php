<?php

declare(strict_types=1);

namespace Tests\Factories;

use Cycle\ORM\TransactionInterface;

abstract class AbstractFactory
{
    /**
     * @param \Cycle\ORM\TransactionInterface $transaction
     */
    public function __construct(
        protected TransactionInterface $transaction
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
}
