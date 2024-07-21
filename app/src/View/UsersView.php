<?php

declare(strict_types=1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Attribute\Singleton;
use Spiral\Http\ResponseWrapper;

#[Singleton]
class UsersView
{
    public function __construct(
        protected ResponseWrapper $response,
        protected UserView $userView,
    ) {
    }

    public function json(array $users): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($users),
        ], 200);
    }

    public function map(array $users): array
    {
        return array_map([$this->userView, 'map'], $users);
    }
}
