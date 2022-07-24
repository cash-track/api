<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Statistics;

use App\Service\Filter\FilterType;
use App\Service\Statistics\Group;
use Tests\TestCase;

class GroupTest extends TestCase
{
    public function testCreateRangeWithInvalidDataThrownException(): void
    {
        $date = $this->getMockBuilder(\DateTimeImmutable::class)->onlyMethods(['format'])->getMock();

        $date->expects($this->once())->method('format')->willReturn('wrong date');

        $this->expectException(\RuntimeException::class);

        Group::ByMonth->createRangeDateByDateAndFilterType($date, FilterType::ByDateFrom);
    }
}
