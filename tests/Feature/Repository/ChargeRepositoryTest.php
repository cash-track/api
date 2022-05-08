<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use App\Database\Charge;
use App\Repository\ChargeRepository;
use Tests\DatabaseTransaction;
use Tests\TestCase;

class ChargeRepositoryTest extends TestCase implements DatabaseTransaction
{
    public function testSumTotalByTypeByCurrencyFromDateEmptyWallets(): void
    {
        $repository = $this->getContainer()->get(ChargeRepository::class);

        $this->assertEquals(0.0, $repository->sumTotalByTypeByCurrencyFromDate(Charge::TYPE_INCOME, []));
    }

    public function testEmptyPaginationState(): void
    {
        $repository = $this->getContainer()->get(ChargeRepository::class);

        $this->assertEquals([], $repository->getPaginationState());
    }
}
