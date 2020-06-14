<?php

declare(strict_types = 1);

namespace App\View;

use Psr\Http\Message\ResponseInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Prototype\Annotation\Prototyped;
use Spiral\Prototype\Traits\PrototypeTrait;

/**
 * @Prototyped(property="usersView")
 */
class UsersView implements SingletonInterface
{
    use PrototypeTrait;

    /**
     * @param \App\Database\User[] $users
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function json(array $users): ResponseInterface
    {
        return $this->response->json([
            'data' => $this->map($users),
        ], 200);
    }

    /**
     * @param \App\Database\User[] $users
     * @return array
     */
    public function map(array $users): array
    {
        return array_map([$this->userView, 'map'], $users);
    }
}
