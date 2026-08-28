<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Charge;
use App\Database\Wallet;
use App\Service\Metrics\AppMetricsInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Injection\Expression;
use Cycle\ORM\EntityManagerInterface;

class ChargeWalletService
{
    const int PRECISION = 2;

    public function __construct(
        private readonly EntityManagerInterface $tr,
        private readonly DatabaseInterface $database,
        private readonly AppMetricsInterface $metrics,
    ) {
    }

    public function create(Wallet $wallet, Charge $charge): Charge
    {
        $this->database->transaction(function () use ($wallet, $charge): void {
            $this->adjustBalance($wallet, $this->delta($charge));

            $this->tr->persist($charge);
            $this->tr->run();
        });

        $this->metrics->incrementChargeCreated($charge->type);
        $this->metrics->incrementTagAssignments($charge->tags->count());

        return $charge;
    }

    public function update(Wallet $wallet, Charge $oldCharge, Charge $newCharge): Charge
    {
        $this->database->transaction(function () use ($wallet, $oldCharge, $newCharge): void {
            $this->adjustBalance($wallet, $this->delta($newCharge) - $this->delta($oldCharge));

            $this->tr->persist($newCharge);
            $this->tr->run();
        });

        return $newCharge;
    }

    public function delete(Wallet $wallet, Charge $charge): void
    {
        $this->database->transaction(function () use ($wallet, $charge): void {
            $this->adjustBalance($wallet, -$this->delta($charge));

            $this->tr->delete($charge);
            $this->tr->run();
        });

        $this->metrics->incrementChargeDeleted($charge->type);
    }

    public function move(Wallet $wallet, Wallet $targetWallet, array $charges): void
    {
        $this->database->transaction(function () use ($wallet, $targetWallet, $charges): void {
            foreach ($charges as $charge) {
                if (! $charge instanceof Charge) {
                    continue;
                }

                $delta = $this->delta($charge);

                $this->adjustBalance($wallet, -$delta);
                $this->adjustBalance($targetWallet, $delta);
                $charge->setWallet($targetWallet);
                $this->tr->persist($charge);
            }

            $this->tr->run();
        });
    }

    public function totalByIncomeAndExpense(float $income, float $expense): float
    {
        return static::safeFloatNumber($income - $expense);
    }

    /**
     * Signed effect a charge has on its wallet's balance: positive for income, negative for
     * expense.
     */
    private function delta(Charge $charge): float
    {
        return match ($charge->type) {
            Charge::TYPE_EXPENSE => -$charge->amount,
            Charge::TYPE_INCOME => $charge->amount,
            default => 0.0,
        };
    }

    /**
     * Atomic `total_amount = total_amount + :delta` UPDATE instead of a PHP read-modify-write,
     * so concurrent charges on the same wallet can't lose an update. Cycle always persists an
     * entity's tracked value verbatim, so the balance is written by raw query and never
     * persist()ed; the entity is then refreshed from the row this UPDATE produced.
     */
    private function adjustBalance(Wallet $wallet, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->database->update('wallets')
            ->set('total_amount', new Expression('total_amount + ?', $delta))
            ->where('id', $wallet->id)
            ->run();

        $row = $this->database->select('total_amount')
            ->from('wallets')
            ->where('id', $wallet->id)
            ->run()
            ->fetch();

        if (is_array($row) && isset($row['total_amount'])) {
            $wallet->totalAmount = static::safeFloatNumber((float) $row['total_amount']);
        }
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
