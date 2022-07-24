<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Statistics;

use App\Service\Statistics\ChargeAmountData;
use Tests\TestCase;

class ChargeAmountDataTest extends TestCase
{
    public function testSetIncomeValidateDate(): void
    {
        $data = [
            [
                'date' => 'wtf-date',
                'total' => 123,
            ],
        ];

        $instance = new ChargeAmountData();

        $instance->setIncome($data);
        $instance->setExpense($data);

        $this->assertEmpty($instance->format());
    }
}
