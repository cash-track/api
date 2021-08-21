<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\User;
use Psr\Http\Message\ResponseInterface;
use Spiral\Auth\AuthScope;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

final class UsersController
{
    use PrototypeTrait;

    /**
     * @var \App\Database\User
     */
    private $user;

    /**
     * UsersController constructor.
     *
     * @param \Spiral\Auth\AuthScope $auth
     */
    public function __construct(AuthScope $auth)
    {
        $this->user = $auth->getActor();
    }

    /**
     * @Route(route="/users/find/by-email/<query>", name="users.find.by-email", methods="GET", group="auth")
     *
     * @param string $query
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function findByEmail(string $query): ResponseInterface
    {
        if ($query === $this->user->email) {
            return $this->response->json(['data' => null], 404);
        }

        $user = $this->users->findByEmail($query);

        if (! $user instanceof User) {
            return $this->response->json(['data' => null], 404);
        }

        return $this->userView->json($user);
    }

    /**
     * @Route(route="/users/find/by-common-wallets", name="users.find.by-common-wallets", methods="GET", group="auth")
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function findByCommonWallets(): ResponseInterface
    {
        /** @var \App\Database\User $user */
        $user = $this->auth->getActor();

        /** @var \App\Database\User[] $users */
        $users = $this->users->findByCommonWallets($user);

        return $this->usersView->json($users);
    }
}
