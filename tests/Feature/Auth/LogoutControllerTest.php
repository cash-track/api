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

        // TODO. Remove once token blacklist implemented
        $this->markTestSkipped('Skipped due to tokens blacklist not implemented');

        $response = $this->withAuth($auth)->get('/profile');
        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testUnableRefresh(): void
    {
        // TODO. Remove once token blacklist implemented
        $this->markTestSkipped('Skipped due to tokens blacklist not implemented');

        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuth($auth)->post('/auth/logout');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $response = $this->withAuthRefresh($auth)->post('/auth/refresh', [
            'accessToken' => $auth['accessToken'],
        ]);
        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }
}
