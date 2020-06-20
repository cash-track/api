<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Charge;
use App\Database\Wallet;
use Cycle\ORM\TransactionInterface;
use Spiral\Prototype\Annotation\Prototyped;

/**
 * @Prototyped(property="chargeWalletService")
 */
class ChargeWalletService
{
    /**
     * @var \Cycle\ORM\TransactionInterface
     */
    private $tr;

    /**
     * ChargeWalletService constructor.
     *
     * @param \Cycle\ORM\TransactionInterface $tr
     */
    public function __construct(TransactionInterface $tr)
    {
        $this->tr = $tr;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\Charge $charge
     * @return \App\Database\Charge
     * @throws \Throwable
     */
    public function create(Wallet $wallet, Charge $charge): Charge
    {
        $wallet = $this->apply($wallet, $charge);

        $this->tr->persist($charge);
        $this->tr->persist($wallet);
        $this->tr->run();

        return $charge;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\Charge $oldCharge
     * @param \App\Database\Charge $newCharge
     * @return \App\Database\Charge
     * @throws \Throwable
     */
    public function update(Wallet $wallet, Charge $oldCharge, Charge $newCharge) : Charge
    {
        $wallet = $this->rollback($wallet, $oldCharge);
        $wallet = $this->apply($wallet, $newCharge);

        $this->tr->persist($newCharge);
        $this->tr->persist($wallet);
        $this->tr->run();

        return $newCharge;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\Charge $charge
     * @throws \Throwable
     */
    public function delete(Wallet $wallet, Charge $charge): void
    {
        $wallet = $this->rollback($wallet, $charge);

        $this->tr->delete($charge);
        $this->tr->persist($wallet);
        $this->tr->run();
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\Charge $charge
     * @return \App\Database\Wallet
     */
    protected function apply(Wallet $wallet, Charge $charge): Wallet
    {
        switch ($charge->type) {
            case Charge::TYPE_EXPENSE:
                $wallet->totalAmount = $wallet->totalAmount - $charge->amount;
                break;
            case Charge::TYPE_INCOME:
                $wallet->totalAmount = $wallet->totalAmount + $charge->amount;
                break;
        }

        return $wallet;
    }

    /**
     * @param \App\Database\Wallet $wallet
     * @param \App\Database\Charge $charge
     * @return \App\Database\Wallet
     */
    protected function rollback(Wallet $wallet, Charge $charge): Wallet
    {
        switch ($charge->type) {
            case Charge::TYPE_EXPENSE:
                $wallet->totalAmount = $wallet->totalAmount + $charge->amount;
                break;
            case Charge::TYPE_INCOME:
                $wallet->totalAmount = $wallet->totalAmount - $charge->amount;
                break;
        }

        return $wallet;
    }
}
