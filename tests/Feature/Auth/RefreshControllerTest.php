<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\Jwt\RefreshTokenStorage;
use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\TestCase;

class RefreshControllerTest extends TestCase implements DatabaseTransaction
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

    /**
     * @param int $userId
     * @param \DateTimeImmutable|null $expiredAt
     * @return string
     * @throws \Throwable
     */
    protected function getRefreshToken(int $userId, \DateTimeImmutable $expiredAt = null): string
    {
        /** @var RefreshTokenStorage $tokenStorage */
        $tokenStorage = $this->app->get(RefreshTokenStorage::class);

        return $tokenStorage->create([
            'sub' => $userId,
            'kind' => 'refresh',
        ], $expiredAt)->getID();
    }

    public function testRefresh(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuthRefresh($auth)->post('/auth/refresh', [
            'accessToken' => $auth['accessToken'],
        ]);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $newAuth = $this->getJsonResponseBody($response);

        $this->assertNotEquals($auth['accessToken'], $newAuth['accessToken']);
        $this->assertNotEquals($auth['refreshToken'], $newAuth['refreshToken']);

        $response = $this->withAuth($newAuth)->get('/profile');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        // TODO. Add checking to access protected endpoints once token blacklist implemented
    }

    public function testRefreshWithoutAccessToken(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->withAuthRefresh($auth)->post('/auth/refresh');

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $newAuth = $this->getJsonResponseBody($response);

        $this->assertNotEquals($auth['accessToken'], $newAuth['accessToken']);
        $this->assertNotEquals($auth['refreshToken'], $newAuth['refreshToken']);

        $response = $this->withAuth($newAuth)->get('/profile');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        // TODO. Add checking to access protected endpoints once token blacklist implemented
    }

    public function testRefreshFailsMissingToken()
    {
        $response = $this->post('/auth/refresh');

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testRefreshFailsWithExpiredToken()
    {
        $user = $this->userFactory->create();

        $auth = [
            'refreshToken' => $this->getRefreshToken(
                $user->id,
                (new \DateTimeImmutable())->sub(new \DateInterval('PT1S')),
            )
        ];

        $response = $this->withAuthRefresh($auth)->post('/auth/refresh');

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }

    public function testRefreshFailsWithMissingUser()
    {
        $auth = [
            'refreshToken' => $this->getRefreshToken(
                0,
                (new \DateTimeImmutable())->add(new \DateInterval('P1D')),
            )
        ];

        $response = $this->withAuthRefresh($auth)->post('/auth/refresh');

        $this->assertEquals(401, $response->getStatusCode(), $this->getResponseBody($response));
    }
}
