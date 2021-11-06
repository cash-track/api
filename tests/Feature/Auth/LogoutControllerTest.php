<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class LogoutControllerTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->app->get(UserFactory::class);
    }

    public function testWithoutAuth(): void
    {
        $response = $this->post('/auth/logout');
        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('message', $body);
    }

    public function testLoggedOut(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/auth/logout');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        // TODO. Add checking to access protected endpoints once token blacklist implemented
    }

    // TODO. Add checking to access protected endpoints once token blacklist implemented
}
