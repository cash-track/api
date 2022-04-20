<?php

declare(strict_types=1);

namespace Tests\Feature\Controller\Auth\RegisterController;

use Tests\DatabaseTransaction;
use Tests\Factories\UserFactory;
use Tests\Fixtures;
use Tests\TestCase;

class CheckNickNameTest extends TestCase implements DatabaseTransaction
{
    /**
     * @var \Tests\Factories\UserFactory
     */
    protected UserFactory $userFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->getContainer()->get(UserFactory::class);
    }

    public function testNickNameFree(): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => Fixtures::string(),
        ]);

        $response->assertOk();
    }

    public function testClaimed(): void
    {
        $user = $this->userFactory->create();

        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $user->nickName
        ]);

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param string $nickName
     * @return void
     */
    public function testValidation($nickName): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $nickName,
        ]);

        $response->assertUnprocessable();
    }

    public function provideInvalidNickNames(): array
    {
        return UserFactory::invalidNickNames();
    }
}
