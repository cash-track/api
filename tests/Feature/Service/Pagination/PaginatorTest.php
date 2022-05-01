<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Pagination;

use App\Service\Pagination\Paginator;
use Tests\Fixtures;
use Tests\TestCase;

class PaginatorTest extends TestCase
{

    public function testGetParameter(): void
    {
        $paginator = new Paginator();
        $this->assertNull($paginator->getParameter());

        $paginator = new Paginator(25, 0, 'page');
        $this->assertEquals('page', $paginator->getParameter());
    }

    public function testWithLimit(): void
    {
        $paginator = new Paginator();

        $this->assertEquals($limit = Fixtures::integer(), $paginator->withLimit($limit)->getLimit());
        $this->assertNotEquals($limit, $paginator->getLimit());
    }

    public function testWithPage(): void
    {
        $page = 4;
        $paginator = (new Paginator(10))->withCount(120);

        $this->assertEquals($page, $paginator->withPage($page)->getPage());
        $this->assertNotEquals($page, $paginator->getPage());
    }

    public function testGetPage(): void
    {
        $paginator = (new Paginator(10))->withCount(120);

        $this->assertEquals(1, $paginator->getPage());
        $this->assertEquals(12, $paginator->withPage(120)->getPage());
        $this->assertEquals(2, $paginator->withPage(2)->getPage());
    }

    public function testIsRequired(): void
    {
        $this->assertTrue((new Paginator(10))->withCount(120)->isRequired());
        $this->assertFalse((new Paginator(10))->withCount(10)->isRequired());
    }

    public function testGetPreviousPage(): void
    {
        $this->assertEquals(2, (new Paginator(10))->withCount(120)->withPage(3)->previousPage());
        $this->assertNull((new Paginator(10))->withCount(120)->withPage(1)->previousPage());
    }

    public function testGetNextPage(): void
    {
        $this->assertEquals(4, (new Paginator(10))->withCount(120)->withPage(3)->nextPage());
        $this->assertNull((new Paginator(10))->withCount(120)->withPage(12)->nextPage());
    }
}
