<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth;

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

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    /**
     * @param int $userId
     * @param \DateTimeImmutable|null $expiredAt
     * @return string
     * @throws \Throwable
     */
    protected function getRefreshToken(int $userId, ?\DateTimeImmutable $expiredAt = null): string
    {
        /** @var RefreshTokenStorage $tokenStorage */
        $tokenStorage = $this->getContainer()->get(RefreshTokenStorage::class);

        return $tokenStorage->create([
            'sub' => $userId,
            'kind' => 'refresh',
        ], $expiredAt)->getID();
    }

    public function testRefresh(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        $response = $this->post('/v1/auth/refresh', [
            'refreshToken' => $auth['refreshToken'],
        ]);

        $response->assertOk();

        $newAuth = $this->getJsonResponseBody($response);

        $this->assertNotEquals($auth['accessToken'], $newAuth['accessToken']);
        $this->assertNotEquals($auth['refreshToken'], $newAuth['refreshToken']);

        $response = $this->withAuth($newAuth)->get('/v1/profile');
        $response->assertOk();

        // TODO. Add checking to access protected endpoints once token blacklist implemented
    }

    public function testRefreshFailsMissingToken()
    {
        $response = $this->post('/v1/auth/refresh');

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('refreshToken', $body['errors']);
    }

    public function testRefreshFailsAuthorizationHeaderAloneIsNotAccepted(): void
    {
        $auth = $this->makeAuth($this->userFactory->create());

        // the old header-only flow is no longer accepted, the token must be in the body
        $response = $this->withAuthRefresh($auth)->post('/v1/auth/refresh');

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('refreshToken', $body['errors']);
    }

    public function testRefreshFailsMalformedRefreshToken(): void
    {
        $response = $this->post('/v1/auth/refresh', [
            'refreshToken' => 'not-a-jwt-shaped-token',
        ]);

        $response->assertUnprocessable();

        $body = $this->getJsonResponseBody($response);

        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('refreshToken', $body['errors']);
    }

    public function testRefreshFailsWithTamperedTokenSignature(): void
    {
        $segments = explode('.', $this->getRefreshToken(0));
        $segments[2] = 'tamperedSignatureAaBbCcDdEeFf012345';
        $tamperedToken = implode('.', $segments);

        $response = $this->post('/v1/auth/refresh', ['refreshToken' => $tamperedToken]);

        $response->assertUnauthorized();
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

        $response = $this->post('/v1/auth/refresh', ['refreshToken' => $auth['refreshToken']]);

        $response->assertUnauthorized();
    }

    public function testRefreshFailsWithMissingUser()
    {
        $auth = [
            'refreshToken' => $this->getRefreshToken(
                0,
                (new \DateTimeImmutable())->add(new \DateInterval('P1D')),
            )
        ];

        $response = $this->post('/v1/auth/refresh', ['refreshToken' => $auth['refreshToken']]);

        $response->assertUnauthorized();
    }
}
