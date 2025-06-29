<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Charge;
use App\Database\Wallet;
use Cycle\ORM\EntityManagerInterface;

class ChargeWalletService
{
    const int PRECISION = 2;

    public function __construct(private readonly EntityManagerInterface $tr)
    {
    }

    public function create(Wallet $wallet, Charge $charge): Charge
    {
        $wallet = $this->apply($wallet, $charge);

        $this->tr->persist($charge);
        $this->tr->persist($wallet);
        $this->tr->run();

        return $charge;
    }

    public function update(Wallet $wallet, Charge $oldCharge, Charge $newCharge): Charge
    {
        $wallet = $this->rollback($wallet, $oldCharge);
        $wallet = $this->apply($wallet, $newCharge);

        $this->tr->persist($newCharge);
        $this->tr->persist($wallet);
        $this->tr->run();

        return $newCharge;
    }

    public function delete(Wallet $wallet, Charge $charge): void
    {
        $wallet = $this->rollback($wallet, $charge);

        $this->tr->delete($charge);
        $this->tr->persist($wallet);
        $this->tr->run();
    }

    public function move(Wallet $wallet, Wallet $targetWallet, array $charges): void
    {
        foreach ($charges as $charge) {
            if (! $charge instanceof Charge) {
                continue;
            }

            $this->rollback($wallet, $charge);
            $this->apply($targetWallet, $charge);
            $charge->setWallet($targetWallet);
            $this->tr->persist($charge);
        }

        $this->tr->persist($wallet);
        $this->tr->persist($targetWallet);
        $this->tr->run();
    }

    public function totalByIncomeAndExpense(float $income, float $expense): float
    {
        return static::safeFloatNumber($income - $expense);
    }

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

        $wallet->totalAmount = static::safeFloatNumber($wallet->totalAmount);

        return $wallet;
    }

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

        $wallet->totalAmount = static::safeFloatNumber($wallet->totalAmount);

        return $wallet;
    }

    public function totalSafeCheck(Wallet $wallet, float $income, float $expense): void
    {
        $total = $this->totalByIncomeAndExpense($income, $expense);

        if ($wallet->totalAmount === $total) {
            return;
        }

        $wallet->totalAmount = $total;

        $this->tr->persist($wallet);
        $this->tr->run();
    }

    public static function safeFloatNumber(float $number): float
    {
        return round($number, self::PRECISION);
    }
}
