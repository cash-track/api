<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Database\User;

trait AuthAsserts
{
    protected function assertUserCanLogin(User $user, string $password): void
    {
        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk();
    }

    protected function assertUserCannotLogin(User $user, string $password): void
    {
        $response = $this->post('/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(400);
    }
}
