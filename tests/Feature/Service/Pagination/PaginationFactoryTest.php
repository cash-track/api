<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Pagination;

use App\Service\Pagination\PaginationFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Core\Exception\ScopeException;
use Spiral\Core\FactoryInterface;
use Tests\TestCase;

class PaginationFactoryTest extends TestCase
{

    public function testCreatePaginatorWithPage(): void
    {
        $this->mock(ServerRequestInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->once())->method('getQueryParams')->willReturn(['page' => 2]);
        });

        $factory = $this->getContainer()->get(PaginationFactory::class);

        $factory->createPaginator();
    }

    public function testCreatePaginatorThrownScopeException(): void
    {
        $factory = $this->getContainer()->get(PaginationFactory::class);

        $this->expectException(ScopeException::class);

        $factory->createPaginator();
    }

    public function testCreatePaginatorThrownRuntimeException(): void
    {
        $this->mock(FactoryInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->once())->method('make')->willReturn(null);
        });

        $this->mock(ServerRequestInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->once())->method('getQueryParams')->willReturn(['page' => 2]);
        });

        $factory = $this->getContainer()->get(PaginationFactory::class);

        $this->expectException(\RuntimeException::class);

        $factory->createPaginator();
    }
}
