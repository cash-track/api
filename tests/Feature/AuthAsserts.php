<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\User;

trait AuthAsserts
{
    protected function assertUserCanLogin(User $user, string $password): void
    {
        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));
    }

    protected function assertUserCannotLogin(User $user, string $password): void
    {
        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertEquals(400, $response->getStatusCode(), $this->getResponseBody($response));
    }
}
