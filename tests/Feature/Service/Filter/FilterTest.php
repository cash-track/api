<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Filter;

use App\Repository\ChargeRepository;
use Cycle\ORM\Select;
use Tests\TestCase;

class FilterTest extends TestCase
{
    public function testInjectFilterHandleValueError(): void
    {
        $repository = $this->getContainer()->get(ChargeRepository::class);

        $class = new \ReflectionClass($repository);
        $class->getProperty('filter')->setValue($repository, [
            'unknown' => '123',
        ]);

        $select = $this->getMockBuilder(Select::class)
             ->disableOriginalConstructor()
             ->addMethods(['where'])
             ->getMock();

        $select->expects($this->never())->method('where');

        $this->callMethod($repository, 'injectFilter', [$select]);
    }
}
