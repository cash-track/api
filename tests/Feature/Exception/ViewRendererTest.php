<?php

declare(strict_types=1);

namespace Tests\Feature\Exception;

use App\Repository\CurrencyRepository;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class ViewRendererTest extends TestCase
{
    public function testDefaultActionWorks(): void
    {
        $auth = $this->makeAuth($this->getContainer()->get(UserFactory::class)->create());

        $repoMock = $this->getMockBuilder(CurrencyRepository::class)->onlyMethods(['findAll'])->disableOriginalConstructor()->getMock();
        $repoMock->expects($this->once())->method('findAll')->willThrowException(new \RuntimeException('Error'));

        $this->getContainer()->bind(CurrencyRepository::class, $repoMock);

        $response = $this->withAuth($auth)->get('/currencies');

        $body = $this->getJsonResponseBody($response);

        $response->assertStatus(500);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('error', $body);
        $this->assertEquals(500, $body['status']);
        $this->assertEquals('Error', $body['error']);
    }
}
