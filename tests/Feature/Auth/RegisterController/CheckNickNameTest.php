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
    public function testNickNameFree(): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => Fixture::string(),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testClaimed(): void
    {
        $user = Users::default();
        $this->app->get(UserService::class)->store($user);

        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $user->nickName
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @dataProvider provideInvalidNickNames
     * @param string $nickName
     * @return void
     */
    public function testValidation(string $nickName): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => $nickName,
        ]);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function provideInvalidNickNames(): array
    {
        return array_merge([
            ['',],
            ['as',],
        ], array_map(
            fn ($item) => [Fixture::string() . $item],
            str_split('!@#$%^&*()-=+"\<>,.\''),
        ));
    }
}
