<?php

declare(strict_types=1);

namespace Tests\Feature\Logging;

use App\Repository\CurrencyRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

/**
 * Pins ErrorHandlerMiddleware's log message format: App\Logging\ForwardServerErrorsHandler
 * regex-matches it, so a wording change there would silently break stdout forwarding.
 */
class ForwardServerErrorsHandlerTest extends TestCase implements DatabaseTransaction
{
    public function testUncaught500IsForwardedToDefaultLoggerAsWarning(): void
    {
        // Create the user with the real repository first: UserFactory needs it too,
        // and its constructor is disabled once mocked below.
        $auth = $this->makeAuth($this->getContainer()->get(UserFactory::class)->create());

        $this->mock(CurrencyRepository::class, ['findAll'], function (MockObject $mock) {
            $mock->method('findAll')->willThrowException(new \RuntimeException('db down'));
        });

        $this->mock(LoggerInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->once())
                ->method('warning')
                ->with($this->stringContains('caused the error 500 '));
        });

        $response = $this->withAuth($auth)->get('/v1/currencies');

        $response->assertStatus(500);
    }

    public function testUncaught404IsNotForwarded(): void
    {
        $this->mock(LoggerInterface::class, [], function (MockObject $mock) {
            $mock->expects($this->never())->method('warning');
        });

        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);
    }
}
