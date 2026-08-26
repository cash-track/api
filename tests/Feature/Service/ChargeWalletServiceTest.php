<?php

declare(strict_types=1);

namespace Tests\Feature\Service;

use App\Database\Charge;
use App\Database\Wallet;
use App\Service\ChargeWalletService;
use Tests\DatabaseTransaction;
use Tests\Factories\ChargeFactory;
use Tests\Factories\UserFactory;
use Tests\Factories\WalletFactory;
use Tests\TestCase;

/**
 * The service mutates wallets.total_amount with raw atomic SQL, so every case here runs against
 * the real database rather than a mocked EntityManagerInterface.
 */
class ChargeWalletServiceTest extends TestCase implements DatabaseTransaction
{
    protected UserFactory $userFactory;

    protected WalletFactory $walletFactory;

    protected ChargeFactory $chargeFactory;

    protected ChargeWalletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
        $this->walletFactory = $this->getContainer()->get(WalletFactory::class);
        $this->chargeFactory = $this->getContainer()->get(ChargeFactory::class);
        $this->service = $this->getContainer()->get(ChargeWalletService::class);
    }

    protected function makeWallet(float $totalAmount): Wallet
    {
        $wallet = WalletFactory::make();
        $wallet->totalAmount = $totalAmount;

        return $this->walletFactory->create($wallet);
    }

    protected function makeCharge(string $type, float $amount, Wallet $wallet): Charge
    {
        $user = $this->userFactory->create();

        $charge = ChargeFactory::type(null, $type);
        $charge->amount = $amount;
        $charge->setWallet($wallet);
        $charge->setUser($user);

        return $charge;
    }

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
     */
    public function testCreate(string $type, float $totalAmount, float $chargeAmount, float $expectedTotal): void
    {
        $wallet = $this->makeWallet($totalAmount);
        $charge = $this->makeCharge($type, $chargeAmount, $wallet);

        $this->service->create($wallet, $charge);

        $this->assertEquals($expectedTotal, $wallet->totalAmount);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => $expectedTotal]);
        $this->assertDatabaseHas('charges', ['id' => $charge->id, 'wallet_id' => $wallet->id]);
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
     */
    public function testDelete(string $type, float $totalAmount, float $chargeAmount, float $expectedTotal): void
    {
        $wallet = $this->makeWallet($totalAmount);
        $charge = $this->makeCharge($type, $chargeAmount, $wallet);
        $this->chargeFactory->create($charge);

        $this->service->delete($wallet, $charge);

        $this->assertEquals($expectedTotal, $wallet->totalAmount);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => $expectedTotal]);
        $this->assertDatabaseMissing('charges', ['id' => $charge->id]);
    }

    public function testUpdateAdjustsBalanceByTheDifferenceBetweenOldAndNewCharge(): void
    {
        $wallet = $this->makeWallet(100.0);

        $charge = $this->makeCharge(Charge::TYPE_EXPENSE, 10.0, $wallet);
        $this->service->create($wallet, $charge);

        // 100 - 10 = 90 after the original charge.
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => 90.0]);

        // Mirrors ChargesController::update(): clone for the "old" snapshot, mutate the
        // heap-tracked original as the "new" one. Persisting the clone would make Cycle INSERT
        // an already-existing primary key.
        $oldCharge = clone $charge;
        $charge->amount = 25.0;

        $this->service->update($wallet, $oldCharge, $charge);

        // Rollback the old expense (+10) then apply the new one (-25): 90 + 10 - 25 = 75.
        $this->assertEquals(75.0, $wallet->totalAmount);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => 75.0]);
    }

    public function moveDataProvider(): array
    {
        return [
            [[3.00, 1.01], [1.01, 3.00], [Charge::TYPE_INCOME, 1.99]],
        ];
    }

    /**
     * @dataProvider moveDataProvider
     */
    public function testMove(array $walletAmounts, array $targetWalletAmounts, array $chargeSpec): void
    {
        $wallet = $this->makeWallet($walletAmounts[0]);
        $targetWallet = $this->makeWallet($targetWalletAmounts[0]);

        $charge = $this->makeCharge($chargeSpec[0], $chargeSpec[1], $wallet);
        $this->chargeFactory->create($charge);

        $this->service->move($wallet, $targetWallet, [$charge]);

        $this->assertEquals($walletAmounts[1], $wallet->totalAmount);
        $this->assertEquals($targetWalletAmounts[1], $targetWallet->totalAmount);
        $this->assertEquals($targetWallet->id, $charge->walletId);

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => $walletAmounts[1]]);
        $this->assertDatabaseHas('wallets', ['id' => $targetWallet->id, 'total_amount' => $targetWalletAmounts[1]]);
    }

    /**
     * Two concurrent requests, simulated by cloning the wallet entity so both PHP objects hold
     * the pre-charge balance. A read-modify-write would produce 95 for the second charge,
     * clobbering the first; the atomic UPDATE reads the current DB value, so they net to 85.
     */
    public function testConcurrentChargesOnStaleWalletCopiesDoNotLoseAnUpdate(): void
    {
        $wallet = $this->makeWallet(100.0);

        // Two PHP objects for the same row. Both charges relate to the heap-tracked $wallet:
        // Cycle's identity map is keyed by object identity, so attaching the untracked clone
        // would INSERT an existing row. Only the balance argument differs between "requests".
        $requestA = $wallet;
        $requestB = clone $wallet;

        $chargeA = $this->makeCharge(Charge::TYPE_EXPENSE, 10.0, $wallet);
        $chargeB = $this->makeCharge(Charge::TYPE_EXPENSE, 5.0, $wallet);

        $this->service->create($requestA, $chargeA);

        // requestA's own response correctly reflects the balance right after its own commit.
        $this->assertEquals(90.0, $requestA->totalAmount);

        $this->service->create($requestB, $chargeB);

        // requestB started from a stale in-memory 100, but the atomic UPDATE reads the current
        // DB value — 85, not the 95 a read-modify-write would have produced.
        $this->assertEquals(85.0, $requestB->totalAmount);

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id, 'total_amount' => 85.0]);
    }
}
