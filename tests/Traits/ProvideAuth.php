<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Database\User;
use Tests\Factories\UserFactory;

trait ProvideAuth
{
    protected function makeAuth(User $user, string $password = UserFactory::DEFAULT_PASSWORD): array
    {
        $response = $this->post('/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        $auth = $this->getJsonResponseBody($response);

        // make sure credentials works
        $response = $this->withAuth($auth)->get('/v1/profile');
        $this->assertEquals(200, $response->getStatusCode(), $this->getResponseBody($response));

        return $auth;
    }
}
