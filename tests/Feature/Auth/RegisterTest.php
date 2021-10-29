<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Database\Currency;
use App\Database\User;
use App\Service\UserService;
use Spiral\Database\DatabaseInterface;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected DatabaseInterface|null $db;
    protected UserService|null $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = $this->app->get(DatabaseInterface::class);
        $this->userService = $this->app->get(UserService::class);
    }

    public function testCheckNickNameFree(): void
    {
        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => 'testNickName'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCheckNickNameClaimed(): void
    {
        $user = new User();
        $user->name = 'User';
        $user->nickName = 'testNickName';
        $user->email = 'test@test.com';
        $user->defaultCurrencyCode = Currency::DEFAULT_CURRENCY_CODE;

        $this->userService->store($user);

        $response = $this->post('/auth/register/check/nick-name', [
            'nickName' => 'testNickName'
        ]);

        $this->assertEquals(422, $response->getStatusCode());

        $this->db->delete('users')->where('id', $user->id)->run();
    }
}
