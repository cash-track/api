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
}
