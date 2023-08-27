<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Database\Charge;
use App\Service\ChargeWalletService;
use Cycle\ORM\EntityManagerInterface;
use Tests\Factories\ChargeFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

class ChargeWalletServiceTest extends TestCase
{
    public function createDataProvider(): array
    {
        return [
            [Charge::TYPE_INCOME, 0.0, 0.99, 0.99],
            [Charge::TYPE_INCOME, 0.99, 0.01, 1.00],
            [Charge::TYPE_INCOME, 150.0, 149.99, 299.99],

            [Charge::TYPE_EXPENSE, 0.99, 0.99, 0.0],
            [Charge::TYPE_EXPENSE, 1.0, 0.01, 0.99],
            [Charge::TYPE_EXPENSE, 299.99, 149.99, 150.0],
        ];
    }

    /**
     * @dataProvider createDataProvider
     * @param string $type
     * @param float $totalAmount
     * @param float $chargeAmount
     * @param float $expectedTotal
     * @return void
     * @throws \Throwable
     */
    public function testCreate(string $type, float $totalAmount, float $chargeAmount, float $expectedTotal): void
    {
        $service = new ChargeWalletService(
            $this->getMockBuilder(EntityManagerInterface::class)->getMock()
        );

        $wallet = WalletFactory::make();
        $wallet->totalAmount = $totalAmount;

        $charge = ChargeFactory::income();
        $charge->type = $type;
        $charge->amount = $chargeAmount;

        $service->create($wallet, $charge);

        $this->assertEquals($expectedTotal, $wallet->totalAmount);
    }

    public function deleteDataProvider(): array
    {
        return [
            [Charge::TYPE_INCOME, 0.99, 0.99, 0.0],
            [Charge::TYPE_INCOME, 1.0, 0.01, 0.99],
            [Charge::TYPE_INCOME, 299.99, 149.99, 150.0],

            [Charge::TYPE_EXPENSE, 0.0, 0.99, 0.99],
            [Charge::TYPE_EXPENSE, 0.99, 0.01, 1.00],
            [Charge::TYPE_EXPENSE, 150.0, 149.99, 299.99],
        ];
    }

    /**
     * @dataProvider deleteDataProvider
     * @param string $type
     * @param float $totalAmount
     * @param float $chargeAmount
     * @param float $expectedTotal
     * @return void
     * @throws \Throwable
     */
    public function testDelete(string $type, float $totalAmount, float $chargeAmount, float $expectedTotal): void
    {
        $service = new ChargeWalletService(
            $this->getMockBuilder(EntityManagerInterface::class)->getMock()
        );

        $wallet = WalletFactory::make();
        $wallet->totalAmount = $totalAmount;

        $charge = ChargeFactory::income();
        $charge->type = $type;
        $charge->amount = $chargeAmount;

        $service->delete($wallet, $charge);

        $this->assertEquals($expectedTotal, $wallet->totalAmount);
    }

    public function moveDataProvider(): array
    {
        $charge1 = ChargeFactory::income();
        $charge1->amount = 1.99;

        $charge2 = ChargeFactory::expense();
        $charge2->amount = 1.99;

        $charge3 = ChargeFactory::expense();
        $charge3->amount = 1.01;

        return [
            [[3.00, 1.01], [1.01, 3.00], [$charge1, null]],
            [[3.00, 4.99], [2.01, 0.02], [$charge2, 1]],
            [[3.01, 2.03], [2.02, 3.00], [$charge1, $charge3]],
        ];
    }

    /**
     * @dataProvider moveDataProvider
     * @param array $walletAmounts
     * @param array $targetWalletAmounts
     * @param array $charges
     * @return void
     */
    public function testMove(array $walletAmounts, array $targetWalletAmounts, array $charges)
    {
        $service = new ChargeWalletService(
            $this->getMockBuilder(EntityManagerInterface::class)->getMock()
        );

        $wallet = WalletFactory::make();
        $wallet->totalAmount = $walletAmounts[0];

        $targetWallet = WalletFactory::make();
        $targetWallet->totalAmount = $targetWalletAmounts[0];

        $charge = ChargeFactory::income();
        $charge->type = Charge::TYPE_INCOME;
        $charge->amount = 1.99;

        $service->move($wallet, $targetWallet, $charges);

        $this->assertEquals($walletAmounts[1], $wallet->totalAmount);
        $this->assertEquals($targetWalletAmounts[1], $targetWallet->totalAmount);

        foreach ($charges as $charge) {
            if ($charge instanceof Charge) {
                $this->assertEquals($charge->walletId, $targetWallet->id);
            }
        }
    }
}
