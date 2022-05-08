<?php

declare(strict_types=1);

namespace Tests\Feature\Repository;

use App\Database\Currency;
use App\Repository\CurrencyRepository;
use Tests\DatabaseTransaction;
use Tests\TestCase;

class CurrencyRepositoryTest extends TestCase implements DatabaseTransaction
{
    public function testGetDefaultEmpty(): void
    {
        $repository = $this->getMockBuilder(CurrencyRepository::class)
                           ->disableOriginalConstructor()
                           ->onlyMethods(['findByPK'])
                           ->getMock();

        $repository->method('findByPK')->with(Currency::DEFAULT_CURRENCY_CODE)->willReturn(null);

        $this->assertNull($repository->getDefault());
    }
}
