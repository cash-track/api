<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Charge;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="chargeService")
 */
class ChargeService
{
    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * UserService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     */
    public function __construct(TransactionInterface $tr)
    {
        $this->tr = $tr;
    }

    /**
     * @param \App\Database\Charge $charge
     * @return \App\Database\Charge
     * @throws \Throwable
     */
    public function store(Charge $charge): Charge
    {
        $this->tr->persist($charge);
        $this->tr->run();

        return $charge;
    }

    /**
     * @param \App\Database\Charge $charge
     * @throws \Throwable
     */
    public function delete(Charge $charge): void
    {
        $this->tr->delete($charge);
        $this->tr->run();
    }
}
