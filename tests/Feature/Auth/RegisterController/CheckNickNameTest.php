<?php

declare(strict_types=1);

namespace Tests\Feature\Auth\RegisterController;

use App\Service\UserService;
use Tests\DatabaseTransaction;
use Tests\Fixtures\Fixture;
use Tests\Fixtures\Users;
use Tests\TestCase;

class CheckNickNameTest extends TestCase implements DatabaseTransaction
{
    public function testCheckNickNameFree(): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => Fixture::string(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function provideInvalidNickNames(): array
    {
        return [
            ['',],
            ['as',],
            ['nick-name',]
        ];
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param string $nickName
     * @return void
     */
    public function testCheckNickNameValidation(string $nickName): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $nickName,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function testCheckNickNameClaimed(): void
    {
        $user = Users::default();
        $this->app->get(UserService::class)->store($user);

        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $user->nickName
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
